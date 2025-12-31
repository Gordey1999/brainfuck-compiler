import {numberToCharPretty} from "./CharConverter.mjs";

export class Profiler {
	_rowSize = 20;
	_pointer = 0;
	_pointedCell = null;
	_storage = null;
	_labels = null;
	_labelsMap = null;
	_changed = [];

	constructor(el, storageSize) {
		this._el = el;
		this._storage = Array(storageSize).fill(0);
		this._labels = Array(storageSize).fill(null);
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

			const addressLabel = document.createElement("div");
			const addressValue = document.createElement("div");

			cell.classList.add("tracing-cell");
			address.classList.add("tracing-address");
			value.classList.add("tracing-value");
			char.classList.add("tracing-char");
			addressLabel.classList.add("tracing-address-label");
			addressValue.classList.add("tracing-address-value");

			cell.appendChild(address);
			address.appendChild(addressLabel);
			address.appendChild(addressValue);
			cell.appendChild(char);
			cell.appendChild(value);

			addressValue.textContent = i;

			this._el.appendChild(cell);
			this._renderValue(i, 0);
		}
	}

	_initLabels(code) {
		const lines = code.split("\n");

		const result = [];

		for (let i = 0; i < lines.length; i++) {
			const matches = lines[i].match(/# @memory(.*)/);
			if (matches) {
				const valuesStr = matches[1] + ' ';
				const values = [...valuesStr.matchAll(/(\d+):(.+?)\s/g)];

				for (const pair of values) {
					result.push({
						line: i,
						address: parseInt(pair[1]),
						label: pair[2],
					});
				}
			}
		}

		this._labelsMap = result;
	}

	reset(code) {
		this._initLabels(code);
		this._movePointer(0);
		this._clearChanged();
		this._renderValues(this._storage.slice().fill(0), false);
		this._renderLabels(this._labels.slice().fill(null));
	}

	render(storage, pointer, position) {
		const labels = this._calculateLabels(position)
		this._renderLabels(labels);
		this._clearChanged();
		this._renderValues(storage);
		this._movePointer(pointer);
	}

	_calculateLabels(position) {
		const currentLine = position !== null ? position[0] : Number.MAX_SAFE_INTEGER;
		const labels = Array(this._labels.length).fill(null);
		for (const row of this._labelsMap) {
			if (row.line > currentLine) { break; }

			labels[row.address] = row.label;
		}

		return labels;
	}

	_renderLabels(labels) {
		const count = this._labels.length;
		for (let i = 0; i < count; i++) {
			if (this._labels[i] !== labels[i]) {

				this._renderLabel(i, labels[i]);
				this._labels[i] = labels[i];
			}
		}
	}

	_renderLabel(i, value) {
		const child = this._el.children[i];
		if (child) {
			const valueEl = child.querySelector('.tracing-address-label');
			valueEl.textContent = value === null ? '' : value;
		}
	}

	_renderValues(storage, markChanged = true) {
		const count = this._storage.length;
		for (let i = 0; i < count; i++) {
			if (this._storage[i] !== storage[i]) {

				this._renderValue(i, storage[i]);
				if (markChanged) {
					this._setChanged(i);
				}
				this._storage[i] = storage[i];
			}
		}
	}

	_clearChanged() {
		for (const el of this._changed) {
			const valueEl = el.querySelector('.tracing-value');
			valueEl.classList.remove('--changed');
		}
		this._changed = [];
	}

	_setChanged(i) {
		const child = this._el.children[i];
		if (child) {
			const valueEl = child.querySelector('.tracing-value');
			valueEl.classList.add('--changed');
			this._changed.push(child);
		}
	}

	_renderValue(i, value) {
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
		if (address >= 0 && address < this._storage.length) {
			this._pointedCell = this._el.children[address];
			this._pointedCell.classList.add('--active');
		}
	}
}