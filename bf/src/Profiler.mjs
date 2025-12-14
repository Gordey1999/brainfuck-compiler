import {numberToCharPretty} from "./CharConverter.mjs";

export class Profiler {
	_rowSize = 20;
	_pointer = 0;
	_pointedCell = null;

	constructor(el, storageSize) {
		this._el = el;
		this._storage = Array(storageSize).fill(0);
		this._build();
		this._movePointer(0);
	}

	_build() {
		const size = this._storage.length;
		for (let i = 0; i < size; i++) {
			const cell = document.createElement("div");
			const address = document.createElement("div");
			const value = document.createElement("div");
			const char = document.createElement("div");

			cell.classList.add("tracing-cell");
			address.classList.add("tracing-address");
			value.classList.add("tracing-value");
			char.classList.add("tracing-char");

			cell.appendChild(address);
			cell.appendChild(char);
			cell.appendChild(value);

			address.textContent = i;

			this._el.appendChild(cell);
			this._render(i, 0);
		}
	}

	reset() {
		this._movePointer(0);
		this._storage.fill(0);

		for (const child of this._el.children) {
			child.querySelector('.tracing-value').textContent = '';
			child.querySelector('.tracing-char').textContent = '';
		}
	}

	render(storage, pointer) {
		this._movePointer(pointer);

		const count = this._storage.length;
		for (let i = 0; i < count; i++) {
			if (this._storage[i] !== storage[i]) {

				this._render(i, storage[i]);
				this._storage[i] = storage[i];
			}
		}
	}

	_render(i, value) {
		const child = this._el.children[i];
		if (child) {
			const valueEl = child.querySelector('.tracing-value');
			valueEl.textContent = value;
			child.querySelector('.tracing-char').textContent = numberToCharPretty(value);

			valueEl.classList.toggle('--empty', value === 0);
		}
	}

	_movePointer(address) {
		if (this._pointedCell !== null) {
			this._pointedCell.classList.remove('--active');
			this._pointedCell = null;
		}

		this._pointer = address;
		if (address < this._storage.length) {
			this._pointedCell = this._el.children[address];
			this._pointedCell.classList.add('--active');
		}
	}
}