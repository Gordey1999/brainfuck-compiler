import fs from 'fs';
import path from 'path';
import MarkdownIt from 'markdown-it';
import markdownItAnchor from 'markdown-it-anchor';
import { DocHighlighter } from './DocHighlighter.js';
import {bfLanguage, bfHighlight} from '../ide/src/lib/highlight/bf-lang.ts';
import {bfxLanguage, bfxHighlight} from '../ide/src/lib/highlight/bfx-lang.ts';

// npm run build:docs

const docFiles = [
	{ filename: 'index.md', title: 'Главная страница', hideOnMenu: true },
	{ filename: 'language.md', title: 'Документация по языку' },
	{ filename: 'examples.md', title: 'Примеры программ', divider: true },
	{ filename: 'studio.md', title: 'Руководство Brainfix Studio' },
	{ filename: 'en/index.md', title: 'Home page', hideOnMenu: true },
	{ filename: 'en/language.md', title: 'Syntax Documentation' },
	{ filename: 'en/examples.md', title: 'Code Examples', divider: true },
	{ filename: 'en/studio.md', title: 'Brainfix Studio Guide' },
];

const highlighter = new DocHighlighter();
highlighter.registerLanguage('brainfuck', bfLanguage, bfHighlight);
highlighter.registerLanguage('brainfix', bfxLanguage, bfxHighlight);

const slugify = (str) => encodeURIComponent(
	String(str)
		.trim()
		.toLowerCase()
		.replace(/\s+/g, '-')
		.replace(/[^\w\sа-яё-]/gi, '')
);

const md = new MarkdownIt({
	html: true,
	highlight: highlighter.getMarkdownHighlightFunction()
}).use(markdownItAnchor, {
	slugify: slugify,
	level: [2]
});

// ПРАВИЛА РЕНДЕРИНГА КОДА
md.renderer.rules.fence = (tokens, idx, options, env, self) => {
	const token = tokens[idx];
	const code = token.content;
	const lang = token.info.trim();

	const highlighted = options.highlight(code, lang);

	if (highlighted) {
		return `<div class="doc-code-block">
			<div class="doc-code-header">
				<span class="doc-code-header__lang">${lang || 'text'}</span>
				<button class="doc-code-header__copy">скопировать</button>
			</div>
			${highlighted}
		</div>\n`;
	}

	const escapedCode = md.utils.escapeHtml(code);
	return `<div class="doc-code-block">
		<div class="doc-code-header">
			<span class="doc-code-header__lang">${lang || 'text'}</span>
			<button class="doc-code-header__copy">скопировать</button>
		</div>
		<pre class="cm-editor"><code class="cm-scroller">${escapedCode}</code></pre>
	</div>\n`;
};


function build() {
	const srcDir = './docs-src';
	const outDir = './docs/wiki';

	if (!fs.existsSync(outDir)) {
		fs.mkdirSync(outDir, {recursive: true});
	}
	if (!fs.existsSync(path.join(outDir, 'en'))) {
		fs.mkdirSync(path.join(outDir, 'en'), {recursive: true});
	}

	const siteMap = [];

	docFiles.forEach(fileInfo => {
		const filePath = path.join(srcDir, fileInfo.filename);
		if (!fs.existsSync(filePath)) return;

		const markdownInput = fs.readFileSync(filePath, 'utf-8');

		const tokens = md.parse(markdownInput, {});
		const anchors = [];

		for (let i = 0; i < tokens.length; i++) {
			if (tokens[i].type === 'heading_open' && (tokens[i].tag === 'h2')) {
				const inlineToken = tokens[i + 1];
				if (inlineToken && inlineToken.type === 'inline') {
					const titleText = inlineToken.content;
					anchors.push({
						text: titleText,
						level: parseInt(tokens[i].tag.replace('h', '')),
						id: slugify(titleText)
					});
				}
			}
		}

		const item = {
			filename: fileInfo.filename,
			htmlFilename: fileInfo.filename.replace('.md', '.html'),
			title: fileInfo.title,
			anchors: anchors,
			hideOnMenu: fileInfo.hideOnMenu ?? false,
			divider: fileInfo.divider ?? false,
			isEng: fileInfo.filename.startsWith('en/'),
		}

		item.linkUrl = item.isEng ? item.htmlFilename.substring(3) : item.htmlFilename;

		siteMap.push(item);
	});


	const cssDir = path.join(outDir, 'assets/css');
	if (!fs.existsSync(cssDir)) fs.mkdirSync(cssDir, { recursive: true });
	fs.writeFileSync(path.join(cssDir, 'code-theme.css'), highlighter.getCssCode());


	siteMap.forEach(currentPage => {
		const markdownInput = fs.readFileSync(path.join(srcDir, currentPage.filename), 'utf-8');

		let template, addToMenu;
		if (currentPage.isEng) {
			template = fs.readFileSync(path.join(srcDir, 'template-en.html'), 'utf-8');

			addToMenu = siteMap.filter(item => !item.hideOnMenu && item.isEng);
		} else {
			template = fs.readFileSync(path.join(srcDir, 'template.html'), 'utf-8');

			addToMenu = siteMap.filter(item => !item.hideOnMenu && !item.isEng);

		}

		const mainContentHtml = md.render(markdownInput);

		const sidebarHtml = generateSidebarHTML(addToMenu, currentPage.htmlFilename);


		let finalHtml = template
			.replace('<!-- TITLE -->', `${currentPage.title} — BrainFix Docs`)
			.replace('<!-- CONTENT -->', mainContentHtml)
			.replace('<!-- SIDEBAR -->', sidebarHtml);

		const outputFileName = currentPage.htmlFilename;
		fs.writeFileSync(path.join(outDir, outputFileName), finalHtml);
	});

	console.log('🎉 Документация успешно собрана в папку /docs!');
}


function generateSidebarHTML(siteMap, activeHtmlFilename) {
	let html = `<ul class="doc-menu">\n`;

	siteMap.forEach((page) => {
		const isCurrentPage = page.htmlFilename === activeHtmlFilename;
		const activeClass = isCurrentPage ? '--active' : '';

		html += `	<li class="doc-menu-item ${activeClass}">\n`;
		html += `		<div class="doc-menu-item__inner">\n`;
		html += `			<a href="${page.linkUrl}" class="doc-menu-link">${page.title}</a>\n`;

		if (isCurrentPage && page.anchors.length > 0) {
			html += `			<ul class="doc-submenu">\n`;
			page.anchors.forEach(anchor => {
				const subLinkClass = 'doc-submenu-link';
				html += `				<li><a href="#${anchor.id}" class="${subLinkClass}">${anchor.text}</a></li>\n`;
			});
			html += `			</ul>\n`;
		}

		html += `		</div>\n`;
		html += `	</li>\n`;

		if (page.divider) {
			html += '<div class="doc-menu-divider"></div>\n';
		}
	});

	html += `</ul>`;
	return html;
}


build();