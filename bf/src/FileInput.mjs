
export class FileInput {
	constructor(element) {
		this._el = element;
		this._active = false;
		this.set();
	}

	onToggle = () => {
		this._active = !this._active;
		this._el.classList.toggle('--active', this._active);
	}

	get() {
		if (!this._active) {
			return [];
		}
		return this.getTextarea().textContent.split('');
	}

	set(text) {
		this.getTextarea().textContent = text;
	}

	getTextarea() {
		return this._el.querySelector('pre');
	}
}