

export class Console {
	_buffer = [];
	_streamIn = [];

	constructor(el, status, counter) {
		this._el = el;
		this._status = status;
		this._counter = counter;
		this._inputResolve = null;

		this._bind();
	}

	_bind() {
		this._el.addEventListener('paste', this._onPaste);
		this._el.addEventListener('keydown', this._onKey);
	}

	_onKey = (event) => {
		if (event.ctrlKey || event.metaKey) { return; }
		if (event.key === 'Enter') {
			event.preventDefault();
			this._enter();
		} else if (event.key === 'Backspace') {
			event.preventDefault();
			this._backspace();
		} else if (event.key.length === 1) {
			event.preventDefault();
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
		this._resolveInput();
	}

	_resolveInput() {
		if (this._inputResolve) {
			this._inputResolve(this._streamIn);
			this._streamIn = [];
		}
		this._inputResolve = null;
	}

	readInput() {
		this.setStatus('need input');
		return new Promise((resolve) => {
			this._inputResolve = resolve;
		});
	}

	setStatus(status = null) {
		this._status.classList.remove('--loading', '--warning', '--error');

		switch (status) {
			case 'running':
				this._status.textContent = 'RUNNING ';
				this._status.classList.add('--loading');
				break;
			case 'stopped':
				this._status.textContent = 'STOPPED';
				break;
			case 'finished':
				this._status.textContent = 'FINISHED';
				break;
			case 'waiting':
				this._status.textContent = 'WAITING';
				break;
			case 'need input':
				this._status.textContent = ' INPUT WAITING ';
				this._status.classList.add('--warning');
				break;
			case 'error':
				this._status.textContent = ' ERROR ';
				this._status.classList.add('--error');
				break;
			default:
				this._status.textContent = '';
		}
	}

	setCommandsCount(count) {
		if (count === 0) {
			this._counter.textContent = '';
		}

		const number = Number(count).toLocaleString("en-US");
		this._counter.textContent = number + ' cmds';
	}

	echo(text) {
		this._el.textContent += text;
	}

	stop() {
		this._resolveInput();
	}

	clear() {
		this.stop();
		this._el.textContent = '';
		this.setCommandsCount(0);
		this.setStatus();
	}

	showError(message) {
		this.setStatus('error');
		if (this._el.textContent.length > 0) {
			this.echo('\n');
		}
		this.echo(message);
	}

	checkBufferSize() {

	}
}