import { describe, expect, it } from 'vitest';
import { normalizeEditorHtml } from '@/core/utils/html';

describe('normalizeEditorHtml', () => {
  it('turns single &nbsp; between words back into spaces', () => {
    expect(normalizeEditorHtml('<p>Hola&nbsp;mundo&nbsp;cruel</p>')).toBe(
      '<p>Hola mundo cruel</p>',
    );
    expect(normalizeEditorHtml('<p><strong>a</strong>&nbsp;b</p>')).toBe(
      '<p><strong>a</strong> b</p>',
    );
  });

  it('keeps runs of &nbsp; (intentional spacing)', () => {
    expect(normalizeEditorHtml('<p>a&nbsp;&nbsp;&nbsp;b</p>')).toBe('<p>a&nbsp;&nbsp;&nbsp;b</p>');
  });

  it('maps the empty document to null', () => {
    expect(normalizeEditorHtml('<p><br></p>')).toBeNull();
    expect(normalizeEditorHtml('')).toBeNull();
    expect(normalizeEditorHtml(null)).toBeNull();
  });
});
