
export type ConsoleStatus = 'running' | 'building' | 'stopped' | 'finished' | 'waiting' | 'need input' | 'error' | '';

export class Console {
    private _buffer: string[] = [];
    private _streamIn: string[] = [];
    private _el: HTMLElement;
    private _status: HTMLElement;
    private _counter: HTMLElement;

    private _inputResolve: ((value: string[]) => void) | null = null;
    private _useInputBuffer: boolean = true;
    private _scrollPending: boolean = false;


    constructor(el: HTMLElement, status: HTMLElement, counter: HTMLElement) {
		this._el = el;
		this._status = status;
		this._counter = counter;

		this.clear();
		this.setStatus('TERMINAL');
		this._bind();
	}

    private _bind(): void {
		this._el.addEventListener('paste', this._onPaste);
		this._el.addEventListener('keydown', this._onKey);
	}

    private _onKey = (event: KeyboardEvent): void => {
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

    private _onPaste = (event: ClipboardEvent): void => {
		const text = event.clipboardData?.getData('text/plain') || '';
		for (const i of text) {
			if (i === '\n') {
				this._enter();
			} else {
				this._input(i);
			}
		}
	}

    private _backspace(): void {
		if (this._buffer.length === 0) { return; }
		this._buffer.pop();
        const currentText = this._el.textContent || '';
        this._el.textContent = currentText.slice(0, -1);
	}

    private _input(char: string): void {
		this._render(char);
		this._buffer.push(char);

		if (!this._useInputBuffer) {
			this._streamIn.push(...this._buffer);
			this._buffer = [];
			this._resolveInput();
		}
	}

    private _enter(): void {
		this._buffer.push('\n');
		this._streamIn.push(...this._buffer);
		this._buffer = [];
		this._render('\n');
		this._resolveInput();
		this._scrollToEnd();
	}

    private _render(text: string): void {
		let content = this._el.textContent || '';

		content += text;

		if (content.length > 10000) {
			content = content.substring(content.length - 10000);
		}

		this._el.textContent = content;
	}

    private _resolveInput(): void {
		if (this._inputResolve) {
			this._inputResolve(this._streamIn);
			this._streamIn = [];
		}
		this._inputResolve = null;
	}

    public readInput(): Promise<string[]> {
		this.setStatus('need input');

		if (this._streamIn.length > 0) {
			const stream = this._streamIn;
			this._streamIn = [];
			return Promise.resolve(stream);
		}

		return new Promise((resolve) => {
			this._inputResolve = resolve;
		});
	}

    public setStatus(status: ConsoleStatus | string = ''): void {
		this._status.classList.remove('--loading', '--warning', '--error');

		switch (status) {
			case 'running':
				this._status.textContent = 'RUNNING ';
				this._status.classList.add('--loading');
				break;
			case 'building':
				this._status.textContent = 'BUILDING ';
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
				this._status.textContent = status;
		}
	}

    public setCommandsCount(count: number): void {
		if (count === 0) {
			this._counter.textContent = '';
            return;
		}

		const number = Number(count).toLocaleString("en-US");
		this._counter.textContent = number + ' cmds';
	}

    public echo(text: string): void {
		this._render(text);
		this._scrollToEnd();
	}

    public stop(): void {
		this._resolveInput();
	}

    public clear(): void {
		this.stop();
		this._streamIn = [];
		this._el.textContent = '';
		this.setCommandsCount(0);
		this.setStatus();
	}

    public showError(message: string): void {
		this.setStatus('error');
        const currentLength = this._el.textContent?.length || 0;
		if (currentLength > 0) {
			this.echo('\n');
		}
		this.echo(message + '\n');
	}

    public setUseInputBuffer(value: boolean = true): void {
		this._useInputBuffer = value;
	}

    public setColor(color: string | null = null): void {
		if (color) {
			document.body.style.setProperty('--console-color', color);
		}
		else {
			document.body.style.removeProperty('--console-color');
		}
	}

    public captureFocus(): void {
		this._el.focus();
	}

    private _scrollToEnd(): void {
		if (!this._scrollPending) {
			this._scrollPending = true;
			requestAnimationFrame(() => {
				this._el.scrollTop = this._el.scrollHeight;
				this._scrollPending = false;
			});
		}
	}
}