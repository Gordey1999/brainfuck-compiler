
const files = [
	{
		url: 'sample/ivan/01_greetings.txt',
		input: '',
		lang: 'bb',
	},
	{
		url: 'sample/ivan/02_types.txt',
		input: '',
		lang: 'bb',
	},
	{
		url: 'sample/ivan/03_operators.txt',
		input: '',
		lang: 'bb',
	},
	{
		url: 'sample/ivan/04_arithmetics.txt',
		input: '',
		lang: 'bb',
	},
	{
		url: 'sample/ivan/05_constructions.txt',
		input: '',
		lang: 'bb',
	},
	{
		url: 'sample/ivan/06_arrays.txt',
		input: 'ямап туртос ,тяничоп оге икинхет ,адеб ен — илибу атобор илсЕ .йивтсйед удобовс юунлоп и ьтсонназаканзеб илавовтсвуч ет ыботч ,йелетитесоп итохирп еыбюл тюянлопыв ыдиордна еыннавориуртснокс оньлаицепс »адапаЗ огокиД риМ« йинечелвзар екрап моксечитсирутуф В\n',
		lang: 'bb',
	},
	{
		url: 'sample/ivan/07_translit.txt',
		input: '',
		lang: 'bb',
	},
	{
		url: 'sample/ivan/08_cursed.txt',
		input: '',
		lang: 'bb',
	},
	{
		url: 'sample/ivan/09_mult.txt',
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