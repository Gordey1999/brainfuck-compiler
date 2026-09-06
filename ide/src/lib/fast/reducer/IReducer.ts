
export interface IReducer {
    getLength(): number;
    getColor(): string;
    compile(): string;
}

export interface IReducerConstructor {
    new (...args: any[]): IReducer;
    match(code: string): IReducer | null;
}