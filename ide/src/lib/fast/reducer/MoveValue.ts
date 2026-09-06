import type {IReducer} from "./IReducer.ts";

export class MoveValue implements IReducer {
    private readonly shifts: number[];
    private readonly length: number;

	constructor(shifts: number[], length: number) {
		this.shifts = shifts;
        this.length = length;
	}

	static match(code: string): IReducer | null {
        // ищем скобочки без скобочек внутри
        // [-<<<+>>>>>+<<] => <<<+>>>>>+<<
        let matches = code.match(/^\[-([^\[\]]+)]/);
        if (!matches) {
            return null;
        }

        // if (/[^><+]/.test(matches[1])) {
        //     return null;
        // }

        const parts = matches[1].split('+');

        let shifts = [];
        for (let part of parts) {
            if (/>+$/.test(part))      shifts.push(part.length);
            else if (/<+$/.test(part)) shifts.push(-part.length);
            else                       return null;
        }

        const sum = shifts.reduce((total, current) => total + current, 0);
        if (sum !== 0) {
            return null;
        }

        return new this(shifts, matches[0].length);
	}

    compile(): string {
        let js = '';
        let totalShift = 0;

        for (let shift of this.shifts) {
            totalShift += shift;

            if (totalShift > 0) {
                js += `m[p + ${totalShift}] = m[p];\n`;
            } else {
                js += `m[p - ${Math.abs(totalShift)}] = m[p];\n`;
            }
        }
        js += "m[p] = 0;\n";

        return js;
    }

	getLength(): number {
		return this.length;
	}

	getColor(): string {
		return 'rgba(26,255,0,0.21)';
	}
}