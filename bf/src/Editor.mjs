import {basicSetup} from "codemirror"
import {EditorView, keymap, Decoration} from "@codemirror/view"
import {StreamLanguage, HighlightStyle, syntaxHighlighting, bracketMatching} from "@codemirror/language"
import {tags} from "@lezer/highlight"
import {indentWithTab} from "@codemirror/commands"
import { StateField, StateEffect } from "@codemirror/state"


const bfLanguage = StreamLanguage.define({
	name: "brainfuck",

	token(stream) {
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
	{ tag: tags.keyword, color: "#952222" },
	{ tag: tags.variableName, color: "#95005b", fontWeight: "bold", textTransform: "uppercase" }
])


const setActivePosition = StateEffect.define()
const activeLineDeco = Decoration.line({
	class: "cm-active-debug-line"
})
const activeCharDeco = Decoration.mark({
	class: "cm-active-debug-char"
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

export class Editor {
	constructor(parent, code = '') {
		this._editor = new EditorView({
			extensions: [
				basicSetup,
				bfLanguage,
				syntaxHighlighting(bfHighlight),
				keymap.of(indentWithTab),
				bracketMatching(),
				activeLineField,
			],
			doc: code,
			parent: parent,
		})
	}

	highlightPosition(position) {
		this._editor.dispatch({effects: setActivePosition.of(position)});
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
}