<?php
namespace Sendportal\Base\Services\Content\IfTagConditionalParser;

/**
 * IfTagConditionalParser
 *
 * Adds simple conditional blocks to SendPortal email templates
 * based on subscriber tags.
 *
 * Supported syntax:
 *
 *  {{ if_tag "Blue" }}
 *	   I like blueberries!
 *  {{ elseif_tag "Yellow" }}
 *     I like bananas.
 *  {{ else }}
 *     Fallback content
 *  {{ endif }}
 *
 * Tag matching is case-insensitive.
 *
 * Usage:
 *   $content = $parser->parse($content, $subscriberTags);
 *
 * @author Miquel Jorba
 */
final class IfTagConditionalParser
{
	private function applyIfTagBlocks(string $content, array $subscriberTagsNorm): string
	{
		$blockPattern = '/\{\{\s*if_tag\s+([^\}]+)\}\}(.*?)\{\{\s*endif\s*\}\}/si';

		do {
			$before = $content;

			$content = preg_replace_callback($blockPattern, function ($m) use ($subscriberTagsNorm) {

				$fullBlock = $m[0];
				$inner     = $m[2];

				// Extraiem IF / ELSEIF / ELSE
				$parts = preg_split(
					'/\{\{\s*(elseif_tag\s+[^\}]+|else)\s*\}\}/i',
					$inner,
					-1,
					PREG_SPLIT_DELIM_CAPTURE
				);

				// Primer IF
				$ifTagRaw = trim($m[1], "\"' ");
				if (in_array(mb_strtolower($ifTagRaw), $subscriberTagsNorm, true)) {
					return $parts[0];
				}

				// ELSEIF / ELSE
				for ($i = 1; $i < count($parts); $i += 2) {
					$directive = $parts[$i];
					$block     = $parts[$i + 1] ?? '';

					if (stripos($directive, 'elseif_tag') === 0) {
						$tag = trim(str_ireplace('elseif_tag', '', $directive), "\"' ");
						if (in_array(mb_strtolower($tag), $subscriberTagsNorm, true)) {
							return $block;
						}
					}

					if (strcasecmp(trim($directive), 'else') === 0) {
						return $block;
					}
				}

				return ''; // cap condició compleix
			}, $content);

		} while ($content !== $before);

		return $content;
	}
}

?>
