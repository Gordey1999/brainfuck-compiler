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
			startState() {
				return { inComment: false };
			},
			token(stream, state) {
				if (stream.sol()) {
					state.inComment = false;
				}

				if (stream.match(/^###.*/)) {
					return "string"
				}

				if (stream.match(/^# @memory.*/)) {
					return "attributeName"
				}

				if (!state.inComment && stream.eat('#')) {
					state.inComment = true
					return "comment"
				}

				if (state.inComment) {
					if (stream.match(/^`-?\d+`/)) {
						return "number"
					}
					if (stream.match(/^R\d+/)) {
						return "variableName"
					}
					if (stream.match(/^[$_a-zA-Z][$_a-zA-Z0-9]*\(\d+\)/)) {
						return "variableName"
					}

					stream.next()
					return "comment"
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
			{ tag: tags.number, color: "#0062c7", fontStyle: "italic" },
			{ tag: tags.variableName, color: "#bd8b29", fontStyle: "italic" },
			{ tag: tags.attributeName, color: "#bd8b29" },
		])

		this._bfExt = [ bfLanguage, syntaxHighlighting(bfHighlight) ];
	}

	_defineBb() {
		const bbLanguage = StreamLanguage.define({
			name: "bigBrain",
			startState() {
				return { inString: false, inComment: false };
			},

			token(stream, state) {
				if (state.inString && stream.eat(state.inString)) {
					state.inString = false
					return "string"
				}
				if (stream.sol()) {
					state.inComment = false;
				}

				if (!state.inString && !state.inComment) {
					if (stream.eat('"')) {
						state.inString = '"'
						return "string"
					}
					if (stream.eat("'")) {
						state.inString = "'"
						return "string"
					}
					if (stream.eat('#')) {
						state.inComment = true
						return "comment"
					}
				}

				if (state.inComment) {
					stream.next()
					return "comment"
				}
				if (state.inString) {
					if (stream.match(/^\\n/)) {
						return "number"
					}

					stream.next()
					return "string"
				}

				if (stream.match(/const|char|int|byte|bool|if|while|for|echo|true|false|in|out|eol/)) {
					return "keyword"
				}

				if (stream.match(/^\d+/)) {
					return "number"
				}
				if (stream.match(/^[$_a-zA-Z][$_a-zA-Z0-9]*/)) {
					return "variableName"
				}

				stream.next()
				return null
			}
		})

		const bbHighlight = HighlightStyle.define([
			{ tag: tags.comment, color: "#777", fontStyle: "italic" },
			{ tag: tags.keyword, color: "#224395", fontWeight: "600" },
			{ tag: tags.string, color: "#1d7f2f" },
			{ tag: tags.number, color: "#0062c7" },
			{ tag: tags.variableName, color: "#a22222" },
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