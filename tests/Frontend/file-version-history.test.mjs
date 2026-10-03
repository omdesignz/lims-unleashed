import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { test } from 'node:test';
import { compileTemplate, parse } from '@vue/compiler-sfc';

const source = readFileSync(new URL('../../resources/js/Components/vap-filemanager/file-version-history.vue', import.meta.url), 'utf8');
const store = readFileSync(new URL('../../resources/js/Stores/fileStore.ts', import.meta.url), 'utf8');

test('version history compiles and never treats storage paths as version text', () => {
  const { descriptor } = parse(source);
  const template = compileTemplate({ id: 'file-version-history', source: descriptor.template.content, filename: 'file-version-history.vue' });

  assert.deepEqual(template.errors, []);
  assert.doesNotMatch(source, /TextDecoder|version\.content/);
  assert.doesNotMatch(store.match(/export interface FileVersion \{([\s\S]*?)\n\}/)[1], /content/);
  assert.match(source, /files\.versions\.compare/);
  assert.match(source, /:disabled="isComparing"/);
  assert.match(source, /role="alert"/);
});

test('revision order stays correct when database timestamps are identical', () => {
  const comparatorBody = source.match(/\.sort\(\(a, b\) => \{([\s\S]*?)\n  \}\)/)[1];
  const compare = new Function('a', 'b', comparatorBody);
  const createdAt = new Date('2026-09-30T07:00:00Z');
  const versions = ['R01', 'R11', 'R02'].map((revision_code) => ({ revision_code, createdAt }));

  assert.deepEqual(versions.sort(compare).map((version) => version.revision_code), ['R11', 'R02', 'R01']);
});

test('comparison fetches authorized text, reports errors, and suppresses repeat requests', async () => {
  const body = source.match(/async function compareVersions\([^)]*\): Promise<void> \{([\s\S]*?)\n\}\n\nfunction closeComparison/)[1];
  const AsyncFunction = Object.getPrototypeOf(async function () {}).constructor;
  const compare = new AsyncFunction(
    'newer', 'older', 'isComparing', 'comparisonError', 'route', 'props', 'fetch',
    'comparisonVersions', 'oldContent', 'newContent', 'diffResult', 'diffLines', 'showComparison',
    body,
  );
  const isComparing = { value: false };
  const comparisonError = { value: '' };
  const comparisonVersions = { value: {} };
  const oldContent = { value: '' };
  const newContent = { value: '' };
  const diffResult = { value: [] };
  const showComparison = { value: false };
  const older = { id: 'version-1' };
  const newer = { id: 'version-2' };
  const calls = [];
  let response = { ok: true, json: async () => ({ older: { text: 'Before\n' }, newer: { text: 'After\n' } }) };
  const execute = () => compare(
    newer, older, isComparing, comparisonError,
    (name, params) => {
      assert.equal(name, 'files.versions.compare');
      assert.deepEqual(params, { file: 'file-1', older: 'version-1', newer: 'version-2' });
      return '/compare';
    },
    { fileId: 'file-1' },
    async (url, options) => { calls.push({ url, options }); return response; },
    comparisonVersions, oldContent, newContent, diffResult,
    (before, after) => [{ value: `${before}->${after}` }], showComparison,
  );

  isComparing.value = true;
  await execute();
  assert.equal(calls.length, 0);

  isComparing.value = false;
  await execute();
  assert.equal(calls.length, 1);
  assert.deepEqual(calls[0], { url: '/compare', options: { credentials: 'same-origin', headers: { Accept: 'application/json' } } });
  assert.equal(oldContent.value, 'Before\n');
  assert.equal(newContent.value, 'After\n');
  assert.deepEqual(diffResult.value, [{ value: 'Before\n->After\n' }]);
  assert.equal(showComparison.value, true);
  assert.equal(isComparing.value, false);

  response = { ok: false, status: 413 };
  await execute();
  assert.match(comparisonError.value, /demasiado grandes/);
  assert.equal(isComparing.value, false);
});

test('file store excludes stale peer-lab records and scopes remembered folders by user and lab', () => {
  const body = store.match(/function belongsToActiveLab\(file: File\): boolean \{([\s\S]*?)\n  \}/)[1];
  const belongsToActiveLab = new Function('file', 'activeLabId', body);

  assert.equal(belongsToActiveLab({ lab_id: 7 }, { value: 7 }), true);
  assert.equal(belongsToActiveLab({ lab_id: 8 }, { value: 7 }), false);
  assert.equal(belongsToActiveLab({ lab_id: 7 }, { value: null }), false);
  assert.match(store, /watch\(activeContextKey,[\s\S]*?files\.value = \[\]/);
  assert.match(store, /currentFolder:\$\{activeContextKey\.value\}/);
  assert.doesNotMatch(store, /localStorage\.getItem\('currentFolder'\)/);
  assert.match(store, /replaceFileCollection\(collection: any\)[\s\S]*?filter\(belongsToActiveLab\)/);
});
