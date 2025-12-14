import {Editor} from "./Editor";
import {Translator} from "./Translator.mjs";

export class Controller {
	constructor(editor, profiler, console) {
		this._editor = editor;
		this._profiler = profiler;
		this._console = console;
		this._translator = new Translator(
			(text) => this._console.echo(text)
		);
		this._stopped = true;
		this._running = false;
	}

	onRun = () => {
		this._compile() && this._run();
	}

	onStop = () => {
		if (this._stopped) { return; }
		this._stopped = true;
		this._console.stop();
		this._console.setStatus('stopped');
		this._editor.highlightLine(0);
	}

	onStep = (out = false) => {
		if (this._running) { return; }
		if (this._stopped) {
			if (!this._compile()) { return; }
			this._renderState();
			return;
		}

		this._run(true, { lineStep: true });
	}

	onStepOut = () => {
		if (this._running || this._stopped) { return; }

		this._run(true, { stepOut: true });
	}

	_compile() {
		this._console.clear();
		try {
			const text = this._editor.getCode();
			this._translator.compile(text);
		}
		catch (e) {
			this._console.showError(e.message);
			this._editor.highlightLine(0);
			console.warn(e);
			return false;
		}
		this._stopped = false;
		return true;
	}

	_run = (debug = false, runParams = {}) => {
		if (this._stopped) {
			this._running = false;
			return;
		}
		this._running = true;
		try {
			this._translator.run(debug, runParams);

			this._running = false;

			if (!this._translator.getCurrentLine()) {
				this._stopped = true;
				this._console.setStatus('finished');
			} else {
				this._console.setStatus('waiting');
			}
		}
		catch (e) {
			if (e.message === 'timeout') {
				this._console.setStatus('running');
				setTimeout(this._run, debug, [ runParams ]);
			} else if (e.message === 'need input') {
				this._console.readInput().then((input) => {
					this._translator.pushInput(input);
					this._run(debug, runParams);
				})
			} else {
				this._console.showError(e.message);
				console.warn(e);
				this._stopped = true;
				this._running = false;
			}
		}
		this._renderState();
	}

	_renderState() {
		this._editor.highlightLine(this._translator.getCurrentLine());
		this._profiler.render(this._translator.getStorage(), this._translator.getPointer());
		this._console.setCommandsCount(this._translator.commandsCount());
	}
}