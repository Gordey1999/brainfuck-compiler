
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

	getRaw() {
		return this.getTextarea().textContent;
	}

	set(text) {
		this.getTextarea().textContent = text;
	}

	getTextarea() {
		return this._el.querySelector('pre');
	}

	isActive() {
		return this._active;
	}

	setActive(active) {
		this._active = active;
		this._el.classList.toggle('--active', this._active);
	}
}