/**
 * Vite Configuration File
 *
 * @package Conjure WP
 */

import { resolve } from "path";
import { defineConfig } from "vite";
import banner from "vite-plugin-banner";

export default defineConfig({
	plugins: [
		banner({
			content: `/**
			* ConjureWP - WordPress Setup Wizard
						*
			* @version 1.0.0
			* @author ConjureWP
			* @link https://conjurewp.com/
			* @license GPLv3
			*/`,
		}),
	],
	build: {
		outDir: "build",
		emptyOutDir: true,
		manifest: false,
		sourcemap: false,
		rollupOptions: {
			input: {
				conjure: resolve(__dirname, "assets/scss/conjure.scss"),
				"conjure.min": resolve(__dirname, "assets/js/conjure.js"),
			},
			output: {
				entryFileNames: "js/[name].js",
				chunkFileNames: "js/chunks/[name]-[hash].js",
				assetFileNames: (assetInfo) => {
					const info = assetInfo.name.split(".");
					const extType = info[info.length - 1];
					if (/\.(css)$/.test(assetInfo.name)) {
						if (assetInfo.name.includes("conjure")) {
							return "css/conjure.min.css";
						}
						return `css / [name].min.[ext]`;
					}
					if (/\.(woff|woff2|eot|ttf|otf)$/.test(assetInfo.name)) {
						return `fonts / [name].[ext]`;
					}
					if (
						/\.(png|jpe?g|gif|svg|webp|ico)$/.test(assetInfo.name)
					) {
						return `images / [name].[ext]`;
					}
					return `assets / [name].[ext]`;
				},
			},
		},
		minify: "terser",
		terserOptions: {
			format: {
				comments: false,
			},
			compress: {
				drop_console: true,
			},
		},
		cssMinify: true,
	},
	css: {
		preprocessorOptions: {
			scss: {
				api: "modern-compiler",
			},
		},
	},
	server: {
		hmr: false,
	},
});
