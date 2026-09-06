
const files = [
	{
		name: 'Игра «САПЕР» (Minesweeper)',
		url: 'sample/saper.bfp',
	},
	{
		name: 'Игра «НИМ»',
		url: 'sample/nim.bfp',
	},
	{
		name: 'Игра «Виселица»',
		url: 'sample/hangman.bfp',
	},
	{
		name: 'Простые примеры',
		url: 'sample/examples.bfp',
	},
	{
		name: 'Home Page',
		url: 'sample/home.bfp',
	}
];

export class SampleStorage {

	static list(): string[] {
		return files.map(file => file.name);
	}

	static async load(id: number) {
		return await this.loadFile(files[id].url);
	}

	static async loadHomePage() {
		return this.load(files.length - 1);
	}

	private static async loadFile(url: string) {
		const response = await fetch(url);

		if (!response.ok) {
			throw new Error(`download error: ${response.statusText}`);
		}

		return await response.json();
	}
}