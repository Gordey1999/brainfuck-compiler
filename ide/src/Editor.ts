import {basicSetup} from "codemirror"
import {EditorView, keymap, Decoration, DecorationSet} from "@codemirror/view"
import { indentWithTab, historyField } from "@codemirror/commands"
import { StateField, StateEffect, EditorState, Extension } from "@codemirror/state"
import {bfExt} from "./lib/highlight/bf-lang";
import {bfxExt} from "./lib/highlight/bfx-lang";

type PositionTuple = [number, number] | null;
type CustomHighlightTuple = [number, number, string] | null;
type ErrorPositionTuple = [number | null, number | null];

const setActivePosition = StateEffect.define<PositionTuple>()
const setCustomHighlight = StateEffect.define<CustomHighlightTuple>()
const setErrorPosition = StateEffect.define<ErrorPositionTuple>()

const activeLineDeco = Decoration.line({
	class: "cm-active-debug-line"
})
const activeCharDeco = Decoration.mark({
	class: "cm-active-debug-char"
})

const errorDeco = Decoration.mark({
	class: "cm-compile-error"
})

const activeLineField = StateField.define<DecorationSet>({
	create() {
		return Decoration.none
	},

	update(deco, tr) {
		deco = deco.map(tr.changes)

		for (let e of tr.effects) {
			if (e.is(setActivePosition)) {
				if (e.value === null) {
					return Decoration.none
				}

				try {
					const line = tr.state.doc.line(e.value[0] + 1);
					const char = line.from + e.value[1];

					deco = Decoration.set([
						activeLineDeco.range(line.from),
						activeCharDeco.range(char, char + 1)
					])
				}
				catch (e) {
					return Decoration.none
				}
			}
		}
		return deco
	},

	provide: f => EditorView.decorations.from(f)
})

const customHighlightField = StateField.define<DecorationSet>({
	create() {
		return Decoration.none
	},

	update(deco, tr) {
		// 1. Сдвигаем уже существующие подсветки, если текст изменился
		deco = deco.map(tr.changes)

		// Нам нужен массив для новых декораций, которые пришли в этой транзакции
		let newDecos: any[]  = []
		let shouldClear = false

		for (let e of tr.effects) {
			if (e.is(setCustomHighlight)) {
				if (e.value === null) {
					shouldClear = true
					break
				}

				try {
					const [from, to, color] = e.value;

					const customDeco = Decoration.mark({
						attributes: { style: `background-color: ${color};` }
					})

					// Вместо return, просто собираем новые декорации в массив
					newDecos.push(customDeco.range(from, to))
				}
				catch (err) {
					// Игнорируем ошибки для невалидных позиций
				}
			}
		}

		if (shouldClear) {
			return Decoration.none
		}

		if (newDecos.length > 0) {
			// Сортируем новые декорации по возрастанию индекса 'from'
			// CodeMirror строго требует, чтобы декорации добавлялись по порядку
			newDecos.sort((a, b) => a.from - b.from)

			// Объединяем старые декорации (deco) с новыми (newDecos)
			deco = deco.update({
				add: newDecos
			})
		}

		return deco
	},

	provide: f => EditorView.decorations.from(f)
})

const compileErrorField = StateField.define({
	create() {
		return Decoration.none
	},

	update(deco, tr) {
		deco = deco.map(tr.changes)

		for (let e of tr.effects) {
			if (e.is(setErrorPosition)) {
				if (e.value[0] === null && e.value[1] === null) {
					return Decoration.none
				}

				try {
					const charFrom = e.value[0] as number
					const length = e.value[1] as number

					if (length === 0) {
						return Decoration.none
					}

					deco = Decoration.set([
						errorDeco.range(charFrom, charFrom + length)
					])
				}
				catch (e) {
					return Decoration.none
				}
			}
		}
		return deco
	},

	provide: f => EditorView.decorations.from(f)
})

const scrollExt = EditorView.scrollMargins.of(() => ({ top: 50, bottom: 50 }));

interface EditorStateWrapper {
    state: EditorState;
    scrollTop: number;
    scrollLeft: number;
}

export interface SerializedStateData {
    scrollTop: number;
    scrollLeft: number;
    serializedState: any;
}

export class Editor {
    private _states: Record<string, EditorStateWrapper | null> = {};
    private _currentState: string | null = null;
    private _editor: EditorView;
    private _onChangeCallback: Array<() => void> = [];
    private _defaultExt: Extension[];
    private _bfExt!: Extension;
    private _bbExt!: Extension;

	constructor(parent: HTMLElement) {
		this._defineBf();
		this._defineBfx();

		this._defaultExt = [
			basicSetup,
			keymap.of([indentWithTab]),
			activeLineField,
			compileErrorField,
			customHighlightField,
			scrollExt,

			EditorView.updateListener.of((update) => {
				if (update.docChanged) {
					for (let callback of this._onChangeCallback) {
						callback();
					}
				}
			})
		];

		this._editor = new EditorView({
			parent: parent,
		})
	}

	public onChange(callback: () => void): void {
		this._onChangeCallback.push(callback);
	}

    public addState(name: string, code: string, language: 'bf' | 'bfx'): void {
        const languageExt = language === 'bf' ? this._bfExt : this._bbExt;

        this._states[name] = {
            state: EditorState.create({
                doc: code,
                extensions: [...this._defaultExt, ...(Array.isArray(languageExt) ? languageExt : [languageExt])],
                selection: { anchor: code.length }
            }),
            scrollTop: 0,
            scrollLeft: 0,
        }
    }

    public switchState(name: string): void {
        this._updateState();

        const state = this._states[name];
        if (!state) {
            throw new Error(`State ${name} not found`);
        }

        this._editor.setState(state.state);
        window.requestAnimationFrame(() => {
            this._editor.scrollDOM.scrollTop = state.scrollTop;
            this._editor.scrollDOM.scrollLeft = state.scrollLeft;
        });
        this._editor.focus();

        this._currentState = name;
    }

    private getState(name: string): EditorStateWrapper {
		if (this._currentState === name) {
			this._updateState();
		}

		return this._states[name]!;
	}

    public getStateCode(name: string): string {
		return this.getState(name).state.doc.toString();
	}

    public clearStates(): void {
		this._states = {};
	}

    public removeState(name: number): void {
		this._states[name] = null;
	}

    private _defineBf(): void {
        this._bfExt = bfExt as Extension;
    }

    private _defineBfx(): void {
        this._bbExt = bfxExt as Extension;
    }

    public highlightPosition(position: [number, number] | null): void {
        this._editor.dispatch({ effects: setActivePosition.of(position) });

        if (position !== null) {
            const line = this._editor.state.doc.line(position[0] + 1);
            this._editor.dispatch({
                effects: EditorView.scrollIntoView(
                    line.from,
                    {
                        y: 'nearest',
                        yMargin: 200,
                    }
                )
            });
        }
    }

    public highlightError(from: number | null = null, length: number | null = null): void {
        this._editor.dispatch({ effects: setErrorPosition.of([from, length]) });
    }

    public highlightCustomRange(from: number | null = null, to: number | null = null, color: string = 'yellow'): void {
        if (from === null) {
            this._editor.dispatch({ effects: setCustomHighlight.of(null) });
        } else {
            if (to === null) return;
            this._editor.dispatch({ effects: setCustomHighlight.of([from, to, color]) });
        }
    }

    public getCode(): string {
        return this._editor.state.doc.toString();
    }


    public getSerializableState(name: string): SerializedStateData {
        const state = this.getState(name);

        let serializedState: any = null;
        try {
            serializedState = state.state.toJSON({ history: historyField });
        } catch (e) {
            console.warn("Не удалось сериализовать стейт для " + name, e);
        }

        return {
            scrollTop: state.scrollTop,
            scrollLeft: state.scrollLeft,
            serializedState: serializedState,
        };
    }

    public setSerializableState(name: string, language: 'bf' | 'bfx', code: string, data: SerializedStateData): void {
        const languageExt = language === 'bf' ? this._bfExt : this._bbExt;

        let finalState: EditorState | null = null;

        if (data.serializedState) {
            try {
                finalState = EditorState.fromJSON(
                    data.serializedState,
                    { extensions: [...this._defaultExt, ...(Array.isArray(languageExt) ? languageExt : [languageExt])] },
                    { history: historyField }
                );
            } catch (e) {
                console.error("Ошибка десериализации для " + name, e);
            }
        }

        if (!finalState) {
            finalState = EditorState.create({
                doc: code,
                extensions: [...this._defaultExt, ...(Array.isArray(languageExt) ? languageExt : [languageExt])]
            });
        }

        this._states[name] = {
            state: finalState,
            scrollTop: data.scrollTop || 0,
            scrollLeft: data.scrollLeft || 0
        };
    }

    private _updateState(): void {
        if (this._currentState !== null) {
            this._states[this._currentState] = {
                state: this._editor.state,
                scrollTop: this._editor.scrollDOM.scrollTop,
                scrollLeft: this._editor.scrollDOM.scrollLeft
            };
        }
    }
}