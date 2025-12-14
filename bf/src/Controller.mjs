import {Editor} from "./Editor";
import {Translator} from "./Translator.mjs";

export class Controller {
	constructor(editor, profiler, console) {
		this._editor = editor;
		this._profiler = profiler;
		this._console = console;
		this._translator = new Translator();

		document.querySelector('.js-run')
			.addEventListener('click', this.onRun);
	}

	onRun = () => {
		const text = this._editor.getCode();
		this._translator.compile(text);

		// todo run(callback)
		// input and tick on callback

		this.run();
	}

	run = () => {
		this._translator.run();
		this._profiler.render(this._translator.getStorage(), this._translator.getPointer());
		const line = this._translator.getCurrentLine();
		console.log('tick');
		this._editor.highlightLine(line);

		if (this._translator.finished()) {
			return;
		}
		setTimeout(this.run);
	}

	wait() {
		return new Promise
	}
}