import type {SerializedStateData} from "./Editor";

export interface SaveTab {
    code: string,
    input: string,
    language: 'bf' | 'bfx',
    isSubtab: boolean,
    editor?: SerializedStateData,
    active?: boolean,
}

export type SaveState = SaveTab[];