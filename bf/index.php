<?php

//plaintext-only
?>

<link rel="stylesheet" href="style.css"/>

<div class="container">
	<div class="nav">
		<div class="buttons">
			<div class="buttons-block">
				<button class="btn js-run">run</button>
				<button class="btn js-run">stop</button>
				<button class="btn js-run">debug</button>
			</div>
			<div class="buttons-block">
				<button class="btn js-run">step</button>
				<button class="btn js-run">out</button>
			</div>
			<div class="buttons-block">
				<button class="btn js-run">input</button>
			</div>
		</div>
		<div class="nav-end">Brainfuck Interpreter
			<div class="nav-end-front">Brainfuck Interpreter</div>
		</div>
	</div>
	<div class="content">
		<div class="left">
			<div class="tabs">
				<div class="tab --active">Hello x</div>
				<div class="tab">other x</div>
			</div>
			<div class="edit-area block"></div>
		</div>
		<div class="right">
			<div class="console block">
				<div class="console-stats">
					<pre class="console-status">RUNNING </pre>
					<pre class="console-commands">123,222 cmds</pre>
				</div>
				<pre class="console-container" tabindex="1">hello</pre>
			</div>
			<div class="tracing block">
				<div class="tracing-container"></div>
			</div>
		</div>
	</div>
</div>

<script src="index.bundle.js"></script>