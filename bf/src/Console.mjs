

export class Console {
	_buffer = [];
	_streamIn = [];

	constructor(el, status) {
		this._el = el;
		this._status = status;

		this._bind();

		window.MyConsole = this; // todo remove
	}

	_bind() {
		this._el.addEventListener('paste', this._onPaste);
		this._el.addEventListener('keydown', this._onKey);
		//this._el.addEventListener('wheel', this._onWheel);
	}

	_onKey = (event) => {
		if (event.ctrlKey || event.metaKey) { return; }
		if (event.key === 'Enter') {
			this._enter();
		} else if (event.key === 'Backspace') {
			this._backspace();
		} else if (event.key.length === 1) {
			this._input(event.key);
		}
	}

	_onPaste = (event) => {
		const text = event.clipboardData.getData('text/plain');
		for (const i of text) {
			if (i === '\n') {
				this._enter();
			} else {
				this._input(i);
			}
		}
	}

	_backspace() {
		if (this._buffer.length === 0) { return; }
		this._buffer.pop();
		this._el.textContent = this._el.textContent.slice(0, -1);
	}

	_input(char) {
		this._el.textContent += char;
		this._buffer.push(char);
	}

	_enter() {
		this._buffer.push('\n');
		this._streamIn.push(...this._buffer);
		this._buffer = [];
		this._el.textContent += '\n';
		console.log(this._streamIn);
	}

	echo(text) {
		this._el.textContent += text;
	}

	checkBufferSize() {

	}
}