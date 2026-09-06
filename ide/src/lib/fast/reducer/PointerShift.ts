import type {IReducer} from "./IReducer.ts";

export class PointerShift implements IReducer {
    private readonly shiftCount: number;

	constructor(shiftCount: number) {
		this.shiftCount = shiftCount;
	}

	static match(code: string): IReducer | null {
		let matches = code.match(/^(>{3,})/);
		if (matches) {
			return new this(matches[0].length);
		}

		matches = code.match(/^(<{3,})/);
		if (matches) {
			return new this(-matches[0].length);
		}

        return null;
	}

    compile(): string {
        return `p += ${this.shiftCount};\n`;
    }

	getLength(): number {
		return Math.abs(this.shiftCount);
	}

	getColor(): string {
		return '#ff990036';
	}
}