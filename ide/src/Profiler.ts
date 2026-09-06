import {numberToCharPretty} from "./lib/CharConverter";

interface MemoryLabel {
	line: number;
	address: number;
	label: string;
}

export class Profiler {
	private _pointedCell: HTMLElement | null = null;
	private _storage: Uint8Array;
	private _labels: (string | null)[];
	private _labelsMap: MemoryLabel[] = [];
	private _changed: HTMLElement[] = [];
	private _el: HTMLElement;

	constructor(el: HTMLElement, storageSize: number) {
		this._el = el;
		this._storage = new Uint8Array(storageSize).fill(0);
		this._labels = Array(storageSize).fill(null);
		this._build();
		this._movePointer(0);
	}

	private _build(): void {
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

			addressValue.textContent = String(i);

			this._el.appendChild(cell);
			this._renderValue(i, 0);
		}
	}

    private _initLabels(code: string): void {
		const lines = code.split("\n");
        const result: MemoryLabel[] = [];

		for (let i = 0; i < lines.length; i++) {
			const matches = lines[i].match(/^\s*#\s*@memory(.*)/);
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

    public reset(code: string): void {
		this._initLabels(code);
		this._movePointer(0);
		this._clearChanged();
		this._renderValues(this._storage.slice().fill(0), false);
		this._renderLabels(this._labels.slice().fill(null));
	}

    public render(storage: ArrayLike<number>, pointer: number, position: [number, number] | null): void {
		const labels = this._calculateLabels(position)
		this._renderLabels(labels);
		this._clearChanged();
		this._renderValues(storage);
		this._movePointer(pointer);
	}

    private _calculateLabels(position: [number, number] | null): (string | null)[] {
		const currentLine = position !== null ? position[0] : Number.MAX_SAFE_INTEGER;
        const labels = Array<string | null>(this._labels.length).fill(null);
		for (const row of this._labelsMap) {
			if (row.line > currentLine) { break; }

			labels[row.address] = row.label;
		}

		return labels;
	}

    private _renderLabels(labels: (string | null)[]): void {
		const count = this._labels.length;
		for (let i = 0; i < count; i++) {
			if (this._labels[i] !== labels[i]) {

				this._renderLabel(i, labels[i]);
				this._labels[i] = labels[i];
			}
		}
	}

    private _renderLabel(i: number, value: string | null): void {
        const child = this._el.children[i] as HTMLElement | undefined;
		if (child) {
			const valueEl = child.querySelector('.tracing-address-label') as HTMLElement;
			valueEl.textContent = value === null ? '' : value;
		}
	}

    private _renderValues(storage: ArrayLike<number>, markChanged: boolean = true): void {
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

    private _clearChanged(): void {
		for (const el of this._changed) {
			const valueEl = el.querySelector('.tracing-value') as HTMLElement;
			valueEl.classList.remove('--changed');
		}
		this._changed = [];
	}

    private _setChanged(i: number): void {
		const child = this._el.children[i] as HTMLElement;
		if (child) {
			const valueEl = child.querySelector('.tracing-value') as HTMLElement;
			valueEl.classList.add('--changed');
			this._changed.push(child);
		}
	}

    private _renderValue(i: number, value: number): void {
		const child = this._el.children[i] as HTMLElement | undefined;
		if (child) {
			const valueEl = child.querySelector('.tracing-value') as HTMLElement;
			valueEl.textContent = String(value);
			child.querySelector('.tracing-char')!.textContent = numberToCharPretty(value);

			valueEl.classList.toggle('--empty', value === 0);
		}
	}

    private _movePointer(address: number): void {
		if (this._pointedCell !== null) {
			this._pointedCell.classList.remove('--active');
			this._pointedCell = null;
		}

		if (address >= 0 && address < this._storage.length) {
			this._pointedCell = this._el.children[address] as HTMLElement;
			this._pointedCell.classList.add('--active');
		}
	}
}