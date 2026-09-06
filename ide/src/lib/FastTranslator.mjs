import {Translator} from "./Translator.ts";
import {Registry} from "./fast/reducer/Registry.ts";

// todo для копирование числа можно внутри использовать MoveValue.match
// todo можно во время выполнения подсвечивать используемые места. Чем чаще используются, тем темнее цвет
// todo генерировать @memory только для первых 500 ячеек

export class FastTranslator extends Translator {

	_registry = new Registry();
	_blocks = null;

	constructor(outputCallback) {
		super(outputCallback);
	}

	compile(code) {
		super.compile(code);
		this._blocks = this._registry.build(this._code);
	}

	run(debug = false, debugParams = {}) {
		this._run(debug, debugParams);
	}

	_run(debug = false, debugParams = {}) {
		const length = this._code.length;

		let i = 0;

		if (debug) {
			this._debugInit(debugParams);
		}

		while (this._current < length && i < this._stepsPerFrame) {
			if (this._blocks.has(this._current)) {

				let vars = {
					pointer: this._pointer,
					memory: this._storage,
				}

				const block = this._blocks.get(this._current);
				block.run(vars);

				this._pointer = vars.pointer;
				this._current += block.getLength();
			} else {
				this._nextStep();
			}
			i++;

			if (debug && this._debugCheck(debugParams)) { return; }
		}

		if (this._current === length) {
			return;
		}

		throw new Error('timeout');
	}

	renderCompile(editor) {
		for (const [pos, block] of this._blocks) {
			editor.highlightCustomRange(
				this._sourceMap[pos],
				this._sourceMap[pos + block.getLength()] + 1,
				block.getColor()
			);
		}
	}
}