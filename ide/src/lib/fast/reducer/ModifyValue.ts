import type {IReducer} from "./IReducer.ts";

export class ModifyValue implements IReducer {
    private readonly delta: number;
    private readonly length: number;

	constructor(delta: number, length: number) {
		this.delta = delta;
        this.length = length;
	}

	static match(code: string): IReducer | null {
		let matches = code.match(/^(\+{2,})/);
		if (matches) {
			return new this(matches[0].length, matches[0].length);
		}

		matches = code.match(/^(-{3,})/);
		if (matches) {
            const limit = matches[0].length % 256; // на случай, если минусов больше 256
			return new this(256 - limit, matches[0].length);
		}

        return null;
	}

    compile(): string {
        return `m[p] += ${this.delta};\n`;
    }

	getLength(): number {
		return this.length;
	}

	getColor(): string {
		return 'rgb(0 43 255 / 21%)';
	}
}