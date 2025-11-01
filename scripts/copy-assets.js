import { cpSync, existsSync, readdirSync, statSync, mkdirSync } from "fs";
import { join, dirname } from "path";

const buildDir = "build";
const assetsDir = "assets";

const copyItem = (src, dest) => {
	if (!existsSync(src)) {
		console.warn(`Source not found: ${src}`);
		return;
	}

	const destParent = dirname(dest);
	if (!existsSync(destParent)) {
		mkdirSync(destParent, { recursive: true });
	}

	if (statSync(src).isDirectory()) {
		cpSync(src, dest, { recursive: true, force: true });
		console.log(`Copied directory: ${src} -> ${dest}`);
	} else {
		cpSync(src, dest, { recursive: false, force: true });
		console.log(`Copied file: ${src} -> ${dest}`);
	}
};

const buildJsPath = join(buildDir, "js");
const assetsJsPath = join(assetsDir, "js");

if (existsSync(buildJsPath)) {
	readdirSync(buildJsPath).forEach((item) => {
		const src = join(buildJsPath, item);
		const dest = join(assetsJsPath, item);
		copyItem(src, dest);
	});
}

copyItem(join(buildDir, "css", "conjure.min.css"), join(assetsDir, "css", "conjure.min.css"));

