import {PointerShift} from "./PointerShift";
import {IReducer} from "./IReducer";
import {ModifyValue} from "./ModifyValue";
import {MoveValue} from "./MoveValue";

export class Registry {

	_types = [
        //MoveArrayPointer,
        MoveValue,
        ModifyValue,
        PointerShift,
	];

	build(code : string) : Map<number, IReducer> {
		let result = new Map<number, IReducer>();
        let pos = 0;

        while (pos < code.length) {
            let found = false;

            for (const type of this._types) {
                const block = type.match(code.slice(pos));

                if (block) {
                    result.set(pos, block);
                    pos += block.getLength();
                    found = true;
                    break;
                }
            }

            if (!found) {
                pos++;
            }
        }

		return result;
	}
}