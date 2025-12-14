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
		this._console.setStatus('stopped');
	}

	onStep = () => {
		if (this._running) { return; }
		if (this._stopped && !this._compile()) {
			return;
		}
		this._stopped = false;
		console.log('step');
		this._step();
	}

	_compile() {
		this._console.clear();
		this._stopped = false;
		try {
			const text = this._editor.getCode();
			this._translator.compile(text);
		}
		catch (e) {
			this._console.showError(e.message);
			console.warn(e);
			return false;
		}
		return true;
	}

	_step = () => {
		if (this._stopped) {
			this._running = false;
			return;
		}
		this._running = true;
		try {
			this._translator.run(true, {
				lineStep: true,
			});

			this._running = false;
			// todo finished
			this._console.setStatus('waiting');
		}
		catch (e) {
			this._processError(e, this._step);
		}
		this._renderState();
	}

	_run = () => {
		if (this._stopped) {
			this._running = false;
			return;
		}
		this._running = true;
		try {
			this._translator.run();
			this._stopped = true;
			this._running = false;
			this._console.setStatus('finished');
		}
		catch (e) {
			this._processError(e, this._run);
		}
		this._renderState();
	}

	_processError(e, runCallback) {
		if (e.message === 'timeout') {
			this._console.setStatus('running');
			setTimeout(runCallback);
		} else if (e.message === 'need input') {
			this._console.readInput().then((input) => {
				this._translator.pushInput(input);
				runCallback();
			})
		} else {
			this._console.showError(e.message);
			console.warn(e);
			this._stopped = true;
			this._running = false;
		}
	}

	_renderState() {
		if (this._stopped) {
			// todo line
		}
		this._editor.highlightLine(this._translator.getCurrentLine());
		this._profiler.render(this._translator.getStorage(), this._translator.getPointer());
		this._console.setCommandsCount(this._translator.commandsCount());
	}
}