/**
 * Normalizes the HTML that PrimeVue's Editor (Quill 2) emits.
 *
 * Quill 2's getSemanticHTML() turns every space into &nbsp; (quill#4509): the
 * stored text then never wraps and searches miss words. A single &nbsp;
 * between two non-space characters is turned back into a plain space; runs of
 * several (intentional extra spacing) are kept. Quill's empty document
 * ("<p><br></p>") becomes null.
 */
export function normalizeEditorHtml(html: string | null | undefined): string | null {
  if (!html || html === '<p><br></p>') return null;
  return html.replace(/(?<=[^\s;])&nbsp;(?=[^&\s<])/g, ' ');
}
