import {StreamLanguage, HighlightStyle, syntaxHighlighting, indentUnit} from "@codemirror/language"
import {tags} from "@lezer/highlight"

interface BfxState {
    inString: string | false;
    inComment: boolean;
    headersArea: boolean;
}

const bfxLanguage = StreamLanguage.define<BfxState>({
	name: "BrainFix",
	startState() {
		return { inString: false, inComment: false, headersArea: true };
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
				state.headersArea = false;
				state.inString = '"'
				return "string"
			}
			if (stream.eat("'")) {
				state.headersArea = false;
				state.inString = "'"
				return "string"
			}
			if (state.headersArea) {
				if (
					stream.match(/^#\s*@title.*/i)
					|| stream.match(/^#\s*@steps_per_frame.*/i)
					|| stream.match(/^#\s*@buffered_input.*/i)
					|| stream.match(/^#\s*@comment_level.*/i)
					|| stream.match(/^#\s*@version.*/i)
					|| stream.match(/^#\s*@console_color.*/i)
				) {
					return "meta"
				}
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
			state.headersArea = false;

			if (stream.match(/^\\n/)) {
				return "number"
			}

			stream.next()
			return "string"
		}

		if (stream.match(/^@[$_a-zA-Z][$_a-zA-Z0-9]*/)) {
			state.headersArea = false;
			return 'modifier'
		}

		if (stream.match(/^(?:char|byte|bool|if|else|do|while|for|in|out|sizeof)\b/)) {
			state.headersArea = false;
			return "keyword"
		}

		if (stream.match(/^(?:true|false|eol)\b/)) {
			state.headersArea = false;
			return "number"
		}

		if (stream.match(/^\d+/)) {
			state.headersArea = false;
			return "number"
		}
		if (stream.match(/^[$_a-zA-Z][$_a-zA-Z0-9]*/)) {
			state.headersArea = false;
			return "variableName"
		}

		stream.next()
		return null
	}
})

const bfxHighlight = HighlightStyle.define([
	{ tag: tags.comment, color: "#777", fontStyle: "italic" },
	{ tag: tags.keyword, color: "#224395", fontWeight: "600" },
	{ tag: tags.string, color: "#367d20" },
	{ tag: tags.number, color: "#0062c7" },
	{ tag: tags.variableName, color: "#a22222" },
	{ tag: tags.modifier, color: "#1395bd", fontWeight: "bold" },
	{ tag: tags.meta, color: "#007a80", fontStyle: "italic" },
])

const bfxExt = [ bfxLanguage, syntaxHighlighting(bfxHighlight), indentUnit.of('    ') ];

export { bfxLanguage, bfxHighlight, bfxExt }