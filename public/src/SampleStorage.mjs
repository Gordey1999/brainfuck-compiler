
const files = [
	{
		url: 'sample/ivan/greetings.txt',
		input: '',
		lang: 'bb',
	},
	{
		url: 'sample/ivan/types.txt',
		input: '',
		lang: 'bb',
	},
	{
		url: 'sample/ivan/operators.txt',
		input: '',
		lang: 'bb',
	},
];


export class SampleStorage {

	static async load() {
		const result = [];
		for (const file of files) {
			result.push({
				code: await this.loadFile(file.url),
				input: file.input,
				lang: file.lang,
			})
		}

		return result;
	}

	static async loadFile(url) {
		try {
			const response = await fetch(url);

			if (!response.ok) {
				throw new Error(`download error: ${response.statusText}`);
			}

			return await response.text();

		} catch (error) {
			console.error("cant download file:", error);
			alert("cant read file");
		}
	}
}