import {charToNumber, numberToChar} from './CharConverter.js';

export interface DebugParams {
	stepOut?: boolean;
	oneStep?: boolean;
	lineStep?: boolean;
}

interface DebugData {
	stopOn?: number;
}

export class Translator {
	private _commentSeparator: string = '#';
	private _storageSize: number = 1000000;
	private _outputCallback: ((char: string) => void) | null = null;
	private _stepsPerFrame: number = 10 * 1000 * 1000;

	private _storage: Uint8Array;
	private _pointer: number = 0;
	private _current: number = 0;
	private _last: number = 0;
	private _inputBuffer: string[] = [];
	private _counter: number = 0;
	private _code: string = '';

	private _linesMap: [number, number][] = [];
	private _scopesStart: Map<number, number> = new Map();
	private _scopesEnd: Map<number, number> = new Map();

	private _debugData: DebugData | null = null;

	constructor(outputCallback: (char: string) => void) {
		this._storage = new Uint8Array(this._storageSize);
		this._outputCallback = outputCallback;
	}

	public compile(code: string): void {
		this._storage.fill(0);
		this._pointer = 0;
		this._current = 0;
		this._last = 0;
		this._inputBuffer = [];
		this._counter = 0;
		this._code = this._sanitize(code);
		this._initScopes();
	}

	private _sanitize(code: string): string {
		this._linesMap = [];

		const lines = code.split("\n");

		const result = [];

		const commands = '+-><[].,'.split('');
		let count = 0;
		for (let i = 0; i < lines.length; i++) {
			const sanitized = this._sanitizeComment(lines[i]);

			for (let j = 0; j < sanitized.length; j++) {
				const command = sanitized[j];
				if (!commands.includes(command)) { continue; }

				this._linesMap[count] = [ i, j ];
				count++;

				result.push(command);
			}
		}

		return result.join('');
	}

	private _sanitizeComment(line: string): string {
		return line.split(this._commentSeparator)[0];
	}

	private _initScopes(): void {
		this._scopesStart = new Map();
		this._scopesEnd = new Map();

		const stack = [];
		const length = this._code.length;
		for (let i = 0; i < length; i++) {
			switch (this._code[i])
			{
				case '[':
					stack.push(i);
					break;
				case ']':
					if (stack.length === 0)
					{
						throw new Error("compile error: no pair for ']'");
					}
					const last = stack.pop()!;
					this._scopesStart.set(i, last);
					this._scopesEnd.set(last, i);
					break;
			}
		}

		if (stack.length > 0)
		{
			throw new Error("compile error: no pair for '['");
		}
	}

	public run(debug: boolean = false, debugParams: DebugParams = {}): void {
		this._run(debug, debugParams);
	}

	private _run(debug: boolean = false, debugParams: DebugParams = {}): void {
		const length = this._code.length;

		let i = 0;

		if (debug) {
			this._debugInit(debugParams);
		}

		while (this._current < length && i < this._stepsPerFrame) {
			this._nextStep();
			i++;
			if (debug && this._debugCheck(debugParams)) { return; }
		}

		if (this._current === length) {
			return;
		}

		throw new Error('timeout');
	}

	private _debugInit(params: DebugParams): void {
		this._debugData = {};

		if (params['stepOut'] === true) {
			let minDistance = this._code.length;

			for (const [start, end] of this._scopesEnd) {
				const distance = end - start;

				if (start < this._current && end >= this._current && distance < minDistance) {
					this._debugData.stopOn = end + 1;
					minDistance = end - start;
				}
			}
		}
	}

	private _debugCheck(params: DebugParams): boolean {
		if (this._debugData?.stopOn === this._current) {
			return true;
		}

		if (params['oneStep'] === true) {
			return true;
		}
		if (params['lineStep'] === true) {
			const lastLine = this._linesMap[this._last][0];
			const currentPosition = this.getCurrentPosition();

			if (currentPosition === null || lastLine !== currentPosition[0]) {
				return true;
			}
		}
		return false;
	}

	private _nextStep(): void {
		const last = this._current;

		switch (this._code[this._current]) {
			case '+':
				this._increment();
				break;
			case '-':
				this._decrement();
				break;
			case '>':
				this._forward();
				break;
			case '<':
				this._back();
				break;
			case '[':
				if (this._value() === 0) {
					this._current = this._scopesEnd.get(this._current)!;
				}
				break;
			case ']':
				if (this._value() > 0) {
					this._current = this._scopesStart.get(this._current)!;
				}
				break;
			case '.':
				this._output();
				break;
			case ',':
				this._input();
				break;
		}
		this._current++;
		this._counter++;
		this._last = last;
	}

	private _value(): number {
		return this._storage[this._pointer];
	}

	private _increment(): void {
		this._storage[this._pointer]++;
	}

	private _decrement(): void {
		this._storage[this._pointer]--;
	}

	private _forward(): void {
		this._pointer++;
		if (this._pointer >= this._storageSize) {
			throw new Error("runtime error: memory pointer is out of range " + this._pointer);
		}
	}

	private _back(): void {
		this._pointer--;
		if (this._pointer < 0) {
			throw new Error("runtime error: memory pointer is out of range " + this._pointer);
		}
	}

	private _output(): void {
		if (this._outputCallback) {
			this._outputCallback(numberToChar(this._value()));
		}
	}

	private _input(): void {
		if (this._inputBuffer.length === 0) {
			throw new Error('need input');
		}
		this._storage[this._pointer] = charToNumber(this._inputBuffer.shift()!);
	}

	public lineToCommand(line: number): number | null {
		for (const i  in this._linesMap) {
			if (this._linesMap[i][0] === line) {
				return parseInt(i);
			}
		}
		return null;
	}

	public pushInput(input: string[]): void {
		this._inputBuffer.push(...input);
	}

	public getCurrentPosition(): [number, number] | null {
		if (!this._linesMap[this._current]) {
			return null;
		}
		return this._linesMap[this._current];
	}

	public getStorage(): Uint8Array {
		return this._storage;
	}

	public getPointer(): number {
		return this._pointer;
	}

	public commandsCount(): number {
		return this._counter;
	}

	public setStepsPerFrame(value: number = 0): void {
		if (value > 0) {
			this._stepsPerFrame = value;
		} else {
			this._stepsPerFrame = 10 * 1000 * 1000;
		}
	}

	public renderCompile(editor: any): void {}
	public renderStep(editor: any): void {}
}