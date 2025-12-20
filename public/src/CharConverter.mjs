

const numbers = [];
for (let i = 0; i < 256; i++) {
	numbers.push(i);
}
const bytes = new Uint8Array(numbers);


const decoder = new TextDecoder('windows-1251');

const charMap = decoder.decode(bytes);
const byteMap = new Map();
for (let i = 0; i < charMap.length; i++) {
	byteMap.set(charMap[i], i);
}

export function charToNumber(char) {
	return byteMap.get(char);
}

export function numberToChar(number) {
	return charMap[number];
}

export function numberToCharPretty(number) {
	if (number <= 31) { return ''; }
	if (number === 32) { return '·'; }
	if (number === 127) { return ''; }

	return numberToChar(number);
}

window.numberToChar = numberToChar;
window.charToNumber = charToNumber;