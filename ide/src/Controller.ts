import {Editor} from "./Editor";
import {DebugParams, Translator} from "./lib/Translator";
import {MetaParser} from "./lib/MetaParser";
import {Compiler} from './lib/fast/Compiler';
import {Profiler} from "./Profiler";
import {Console} from "./Console";
import {FileInput} from "./FileInput";

export class Controller {
    private _editor: Editor;
    private _profiler: Profiler;
    private _console: Console;
    private _input: FileInput;
    private _translator: Translator;

    private _stopped: boolean = true;
    private _running: boolean = false;

	constructor(editor: Editor, profiler: Profiler, console: Console, input: FileInput) {
		this._editor = editor;
		this._profiler = profiler;
		this._console = console;
		this._input = input;
		this._translator = new Translator(
			(text) => this._console.echo(text)
		);
	}

	public onRun = (): void => {
		this._compile() && this._run();
	}

	public onFast = (): void => {
		const compiler = new Compiler();
		const text = this._editor.getCode();
		console.log(compiler.compile(text));
		// тут быстрая версия
	}

	public onStop = (): void => {
		if (this._stopped) { return; }
		this._dropHeaders();
		this._stopped = true;
		this._console.stop();
		this._console.setStatus('stopped');
		this._editor.highlightPosition(null);
	}

	public onStep = (): void => {
		if (this._running) { return; }
		if (this._stopped) {
			if (!this._compile()) { return; }
			this._renderState();
			return;
		}

		this._run(true, { oneStep: true });
	}

	public onStepLine = (): void => {
		if (this._running) { return; }
		if (this._stopped) {
			if (!this._compile()) { return; }
			this._renderState();
			return;
		}

		this._run(true, { lineStep: true });
	}

	public onStepOut = (): void => {
		if (this._running || this._stopped) { return; }

		this._run(true, { stepOut: true });
	}

	private _compile(): boolean {
		this._console.clear();
		try {
			const text = this._editor.getCode();
			this._applyHeaders(text);

			this._editor.highlightCustomRange();
			this._translator.compile(text);
			this._translator.renderCompile(this._editor);
			this._translator.pushInput(this._input.get());
			this._profiler.reset(text);
		}
		catch (e) {
            const message = e instanceof Error ? e.message : 'unknown error';

			this._console.showError(message);
			this._editor.highlightPosition(null);
			console.warn(e);
			return false;
		}
		this._stopped = false;
		return true;
	}

	private _run = (debug: boolean = false, runParams: DebugParams = {}) => {
		if (this._stopped) {
			this._running = false;
			return;
		}
		this._running = true;
		try {
			this._translator.run(debug, runParams);

			this._running = false;
			this._translator.renderStep(this._editor);

			if (this._translator.getCurrentPosition() === null) {
				this._stopped = true;
				this._console.setStatus('finished');
			} else {
				this._console.setStatus('waiting');
			}
		}
		catch (e) {
            const message = e instanceof Error ? e.message : e;

			if (message === 'timeout') {
				this._console.setStatus('running');
				setTimeout(this._run, 0, debug, runParams);
				this._translator.renderStep(this._editor);
			} else if (message === 'need input') {
				this._console.readInput().then((input) => {
					this._translator.pushInput(input);
					this._translator.renderStep(this._editor);
					this._run(debug, runParams);
				})
				this._console.captureFocus();
			} else {
                const message = e instanceof Error ? e.message : 'unknown error';
				this._translator.renderStep(this._editor);
				this._console.showError(message);
				console.warn(e);
				this._stopped = true;
				this._running = false;
			}
		}
		this._renderState();
	}

	private _renderState(): void {
		const position = this._translator.getCurrentPosition();
		this._editor.highlightPosition(position);
		this._profiler.render(this._translator.getStorage(), this._translator.getPointer(), position);
		this._console.setCommandsCount(this._translator.commandsCount());
	}

	private _applyHeaders(code: string): void {
		const headers = MetaParser.parseHeaders(code);

		const bufferedInput = headers['buffered_input'] ?? 'on';
		const stepsPerFrame = headers['steps_per_frame'] ?? '';
		const consoleColor = headers['console_color'] ?? '';

		this._console.setColor(consoleColor);
		this._console.setUseInputBuffer(MetaParser.parseBool(bufferedInput, true));
		this._translator.setStepsPerFrame(MetaParser.parseInt(stepsPerFrame, 0));
	}

	_dropHeaders() {
		this._console.setColor();
		this._console.setUseInputBuffer();
		this._translator.setStepsPerFrame();
	}
}