export class Translator {
	_commentSeparator = '#';
	_storageSize = 30000;
	_outputCallback = null;

	constructor(outputCallback) {
		this._storage = Array(this._storageSize).fill(0);
		this._outputCallback = outputCallback;
	}

	compile(code) {
		this._storage.fill(0);
		this._pointer = 0;
		this._current = 0;
		this._last = 0;
		this._stop = false;
		this._inputBuffer = [];
		this._counter = 0;
		this._code = this._sanitize(code);
		this._initScopes(code);
	}

	_sanitize(code) {
		this._linesMap = [];

		const lines = code.split("\n");

		const result = [];

		let count = 0;
		for (let i = 0; i < lines.length; i++) {
			const sanitized = this._sanitizeLine(lines[i]);

			for (let j in sanitized) {
				this._linesMap[count] = i + 1;
				count++;
			}

			result.push(sanitized);
		}

		return result.join('');
	}

	_sanitizeLine(line) {
		const result = line.split(this._commentSeparator)[0];
		return result.replace(/[^+\-\[\].,><]/g, '');
	}

	_initScopes() {

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
					const last = stack.pop();
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

	run(debug = false, debugParams = {}) {
		this._run(debug, debugParams);
	}

	_run(debug = false, debugParams = {}) {
		console.log('translator run');
		if (this._stop) { return; }
		const length = this._code.length;

		const time = performance.now();
		const checkCount = 10000;
		let i = 0;

		while (true) {
			while (this._current < length && i < checkCount) {
				this._nextStep();
				i++;
				if (debug && this._debugCheck(debugParams)) { return; }
			}

			if (this._current === length) {
				this._stop = true;
				return;
			}

			const passed = performance.now() - time;
			if (passed > 50) {
				throw new Error('timeout');
			}
			i = 0;
		}
	}

	_debugCheck(debugParams) {
		if (debugParams['lineStep'] === true) {
			const lastLine = this._linesMap[this._last];
			const currentLine = this.getCurrentLine();

			if (lastLine !== currentLine) {
				return true;
			}
		}
		return false;
	}

	_nextStep() {
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
					this._current = this._scopesEnd.get(this._current) - 1;
				}
				break;
			case ']':
				if (this._value() > 0) {
					this._current = this._scopesStart.get(this._current) - 1;
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

	_value() {
		return this._storage[this._pointer];
	}

	_increment() {
		this._storage[this._pointer]++;
		if (this._storage[this._pointer] === 256) {
			this._storage[this._pointer] = 0;
		}
	}

	_decrement() {
		this._storage[this._pointer]--;
		if (this._storage[this._pointer] === -1) {
			this._storage[this._pointer] = 255;
		}
	}

	_forward() {
		this._pointer++;
		if (this._pointer >= this._storageSize) {
			throw new Error("runtime error: memory pointer is out of range " + this._pointer);
		}
	}

	_back() {
		this._pointer--;
		if (this._pointer < 0) {
			throw new Error("runtime error: memory pointer is out of range " + this._pointer);
		}
	}

	_output() {
		this?._outputCallback(numberToChar(this._value()));
	}

	_input() {
		if (this._inputBuffer.length === 0) {
			throw new Error('need input');
		}
		this._storage[this._pointer] = charToNumber(this._inputBuffer.shift());
	}

	_lineToCommand(line) {
		for (const i in this._linesMap) {
			if (this._linesMap[i] === line) {
				return i;
			}
		}
		return null;
	}

	pushInput(input) {
		this._inputBuffer.push(...input);
	}

	getCurrentLine() {
		if (!this._linesMap[this._current]) {
			return 0;
		}
		return this._linesMap[this._current];
	}

	getStorage() {
		return this._storage;
	}

	getPointer() {
		return this._pointer;
	}

	commandsCount() {
		return this._counter;
	}

	finished() {
		return this._stop; // todo remove
	}
}