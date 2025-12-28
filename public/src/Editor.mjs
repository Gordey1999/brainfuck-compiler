import {basicSetup} from "codemirror"
import {EditorView, keymap, Decoration} from "@codemirror/view"
import {StreamLanguage, HighlightStyle, syntaxHighlighting, bracketMatching} from "@codemirror/language"
import {tags} from "@lezer/highlight"
import {indentWithTab} from "@codemirror/commands"
import { StateField, StateEffect, Compartment } from "@codemirror/state"

const languageCompartment = new Compartment()

const setActivePosition = StateEffect.define()
const activeLineDeco = Decoration.line({
	class: "cm-active-debug-line"
})
const activeCharDeco = Decoration.mark({
	class: "cm-active-debug-char"
})

const setErrorPosition = StateEffect.define()
const errorDeco = Decoration.mark({
	class: "cm-compile-error"
})

const activeLineField = StateField.define({
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

const compileErrorField = StateField.define({
	create() {
		return Decoration.none
	},

	update(deco, tr) {
		deco = deco.map(tr.changes)

		for (let e of tr.effects) {
			if (e.is(setErrorPosition)) {
				if (e.value === null) {
					return Decoration.none
				}

				try {
					const charFrom = e.value[0]
					const length = e.value[1]

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

export class Editor {
	constructor(parent, code = '') {
		this._defineBf();
		this._defineBb();

		this._editor = new EditorView({
			extensions: [
				basicSetup,
				languageCompartment.of(this._bbExt),
				keymap.of(indentWithTab),
				bracketMatching(),
				activeLineField,
				compileErrorField,
			],
			doc: code,
			parent: parent,
		})
	}

	_defineBf() {
		const bfLanguage = StreamLanguage.define({
			name: "brainfuck",

			token(stream) {
				if (stream.match(/^###.*/)) {
					return "string"
				}

				if (stream.match(/^#.*/)) {
					return "comment"
				}

				if (stream.match(/[><+\-.,]/)) {
					return "keyword"
				}

				if (stream.match(/[\[\]]/)) {
					return "bracket"
				}

				stream.next()
				return null
			}
		})

		const bfHighlight = HighlightStyle.define([
			{ tag: tags.comment, color: "#1d7f2f", fontStyle: "italic" },
			{ tag: tags.keyword, color: "#952222", fontWeight: "bold" },
			{ tag: tags.string, color: "#0062c7", fontStyle: "italic" },
		])

		this._bfExt = [ bfLanguage, syntaxHighlighting(bfHighlight) ];
	}

	_defineBb() {
		const bbLanguage = StreamLanguage.define({
			name: "brainfuck",

			token(stream) {
				if (stream.match(/^#.*/)) {
					return "comment"
				}

				if (stream.match(/"[^"]*"/)) {
					return "string"
				}

				if (stream.match(/'[^']*'/)) {
					return "string"
				}

				if (stream.match(/const|char|int|byte|if|while|for|echo|true|false/)) {
					return "keyword"
				}

				stream.next()
				return null
			}
		})

		const bbHighlight = HighlightStyle.define([
			{ tag: tags.comment, color: "#777", fontStyle: "italic" },
			{ tag: tags.keyword, color: "#224395", fontWeight: "bold" },
			{ tag: tags.string, color: "#1d7f2f" }
		])

		this._bbExt = [ bbLanguage, syntaxHighlighting(bbHighlight) ];
	}

	highlightPosition(position) {
		this._editor.dispatch({effects: setActivePosition.of(position)});
	}

	highlightError(from, length) {
		this._editor.dispatch({effects: setErrorPosition.of([from, length])});
	}

	getCode() {
		return this._editor.state.doc.toString();
	}

	setCode(code) {
		this._editor.dispatch({
			changes: {
				from: 0,
				to: this._editor.state.doc.length,
				insert: code
			}
		});
	}

	setLanguage(language) {
		if (language === 'bb') {
			this._editor.dispatch({
				effects: languageCompartment.reconfigure(this._bbExt),
			});
		} else {
			this._editor.dispatch({
				effects: languageCompartment.reconfigure(this._bfExt),
			});
		}
	}
}