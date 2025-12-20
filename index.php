

<link rel="stylesheet" href="public/style.css"/>

<div class="container">
	<div class="nav">
		<div class="buttons">
			<div class="buttons-block">
				<button class="btn btn-run">run</button>
				<button class="btn btn-stop">stop</button>
				<button class="btn btn-debug" style="display: none">debug</button>
			</div>
			<div class="buttons-block">
				<button class="btn btn-step">step</button>
				<button class="btn btn-line">line</button>
				<button class="btn btn-out">out</button>
			</div>
			<div class="buttons-block">
				<button class="btn btn-input">input</button>
			</div>
		</div>
		<div class="nav-end">Brainfucker 3000
			<div class="nav-end-front">Brainfucker 3000</div>
		</div>
	</div>
	<div class="content">
		<div class="left">
			<div class="tabs">
				<div class="tab tab-plus">+</div>
			</div>
			<div class="edit-area block"></div>
		</div>
		<div class="right">
			<div class="right-top">
				<div class="console block">
					<div class="console-info">
						<pre class="console-status"></pre>
						<pre class="console-commands"></pre>
					</div>
					<pre class="console-container" tabindex="1"></pre>
				</div>
				<div class="console-input block">
					<pre class="console-input-textarea" contenteditable="plaintext-only" spellcheck="false"></pre>
				</div>
			</div>

			<div class="tracing block">
				<div class="tracing-container"></div>
			</div>
		</div>
	</div>
</div>

<script src="public/index.bundle.js"></script>