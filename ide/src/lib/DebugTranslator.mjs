import {Translator} from "./Translator.ts";
import {Registry} from "./fast/reducer/Registry.ts";

export class DebugTranslator extends Translator {

	_usage = new Map();

	constructor(outputCallback) {
		super(outputCallback);
	}

	compile(code) {
		super.compile(code);

		for (let [start, end] of this._scopesEnd) {
			this._usage.set(end, 0);
		}
	}

	_nextStep() {
		if (this._code[this._current] === ']')
		{
			this._usage.set(this._current, this._usage.get(this._current) + 1);
		}

		super._nextStep();
	}

	renderStep(editor) {
		let max = 0;
		for (let [end, count] of this._usage) {
			if (count > max) { max = count; }
		}

		editor.highlightCustomRange();

		for (let [end, count] of this._usage) {
			//if (count / max < 0.1) { continue; }

			//console.log(count, max);
			editor.highlightCustomRange(
				this._sourceMap[this._scopesStart.get(end)],
				this._sourceMap[end] + 1,
				this._calculateColor(count, max)
			)
		}
	}

	_calculateColor(value, max) {
		const logValue = Math.log1p(value);
		const logMax = Math.log1p(max);

		// Получаем коэффициент от 0 до 1, но распределенный по логарифмической шкале
		const ratio = logValue / logMax;

		// Чем больше ratio, тем ближе цвет к чистому синему rgb(0, 0, 255)
		const res = Math.round(255 - (ratio * 255));
		return `rgb(${res}, ${res}, 255)`;
	}
}