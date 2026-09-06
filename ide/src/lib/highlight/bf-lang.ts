import {StreamLanguage, HighlightStyle, syntaxHighlighting} from "@codemirror/language"
import {tags} from "@lezer/highlight"

const bfLanguage = StreamLanguage.define({
	name: "brainfuck",
	startState() {
		return { inComment: false, headersArea: true };
	},
	token(stream, state) {
		if (stream.sol()) {
			state.inComment = false;
		}

		if (stream.match(/^###.*/)) {
			return "string"
		}

		if (!state.inComment) {
			if (state.headersArea) {
				if (
					stream.match(/^#\s*@title.*/i)
					|| stream.match(/^#\s*@steps_per_frame.*/i)
					|| stream.match(/^#\s*@buffered_input.*/i)
					|| stream.match(/^#\s*@console_color.*/i)
				) {
					return "meta"
				}
			}

			if (stream.match(/^#\s*@memory.*/i)) {
				return "meta"
			}

			if (stream.eat('#')) {
				state.inComment = true
				return "comment"
			}
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
			state.headersArea = false;
			return "keyword"
		}

		if (stream.match(/[\[\]]/)) {
			state.headersArea = false;
			return "bracket"
		}

		stream.next()
		return null
	}
})

const bfHighlight = HighlightStyle.define([
	{ tag: tags.comment, color: "#367d20", fontStyle: "italic" },
	{ tag: tags.keyword, color: "#952222", fontWeight: "bold" },
	{ tag: tags.string, color: "#0062c7", fontStyle: "italic" },
	{ tag: tags.number, color: "#0062c7", fontStyle: "italic" },
	{ tag: tags.variableName, color: "#bd8b29", fontStyle: "italic" },
	{ tag: tags.meta, color: "#007a80", fontStyle: "italic" },
])

const bfExt = [ bfLanguage, syntaxHighlighting(bfHighlight) ];

export { bfLanguage, bfHighlight, bfExt };