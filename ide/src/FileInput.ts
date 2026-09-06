
export class FileInput {
    private _el: HTMLElement;
    private _active: boolean;

    constructor(element: HTMLElement) {
		this._el = element;
		this._active = false;
		this.set();
	}

    public onToggle = (): void => {
		this.setActive(!this._active);
	}

    public get(): string[] {
		if (!this._active) {
			return [];
		}

        const text = this.getTextarea().textContent || '';
        const chars = text.split('');
		chars.push('\n');
		return chars;
	}

    public getRaw(): string {
		return this.getTextarea().textContent || '';
	}

    public set(text: string = ''): void {
		this.getTextarea().textContent = text;
	}

    public getTextarea(): HTMLPreElement {
		return this._el.querySelector('pre')!;
	}

    public isActive(): boolean {
		return this._active;
	}

    public setActive(active: boolean): void {
		this._active = active;

		this._el.classList.toggle('--active', this._active);
		const resizer = this._el.previousElementSibling as HTMLElement;

		if (this._active) {
			resizer.classList.remove('--hidden');
			resizer.previousElementSibling!.classList.remove('--full-width');
		} else {
			resizer.classList.add('--hidden');
			resizer.previousElementSibling!.classList.add('--full-width');
		}
	}
}