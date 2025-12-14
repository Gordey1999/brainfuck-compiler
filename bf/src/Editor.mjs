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
	{ tag: tags.keyword, color: "#952222" }
])


const setActiveLine = StateEffect.define()
const activeLineDeco = Decoration.line({
	class: "cm-active-debug-line"
})
const activeLineField = StateField.define({
	create() {
		return Decoration.none
	},

	update(deco, tr) {
		deco = deco.map(tr.changes)

		for (let e of tr.effects) {
			if (e.is(setActiveLine)) {
				const line = tr.state.doc.line(e.value)
				deco = Decoration.set([activeLineDeco.range(line.from)])
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

				activeLineField
			],
			doc: code,
			parent: parent,
		})
	}

	highlightLine(lineNo) {
		if (lineNo <= 0) {
			return; // todo this._editor.dispatch({effects: null});
		}
		this._editor.dispatch({effects: setActiveLine.of(lineNo)});
	}

	getCode() {
		return this._editor.state.doc.toString();
	}
}