import { describe, expect, it } from 'vitest';
import { h } from 'vue';
import AppRepeater from '@/molecules/AppRepeater.vue';
import { mountWithPrime } from '@/test/mount';

type Phone = { number: string };

function mountRepeater(modelValue: Phone[], props: Record<string, unknown> = {}) {
  return mountWithPrime(AppRepeater, {
    props: { modelValue, newItem: () => ({ number: '' }), sortable: true, ...props },
    slots: {
      default: ({ item }: { item: object }) => h('span', { class: 'n' }, (item as Phone).number),
    },
  });
}

describe('AppRepeater', () => {
  it('adds, moves and removes rows through update:modelValue', async () => {
    const wrapper = mountRepeater([{ number: '1' }, { number: '2' }]);

    await wrapper.find('button[aria-label="Bajar"]').trigger('click');
    expect(wrapper.emitted('update:modelValue')![0][0]).toEqual([{ number: '2' }, { number: '1' }]);

    await wrapper.findAll('button[aria-label="Quitar"]')[1].trigger('click');
    expect(wrapper.emitted('update:modelValue')![1][0]).toEqual([{ number: '1' }]);

    await wrapper.findAll('button').at(-1)!.trigger('click');
    expect(wrapper.emitted('update:modelValue')![2][0]).toEqual([
      { number: '1' },
      { number: '2' },
      { number: '' },
    ]);
  });

  it('respects min and max', () => {
    const wrapper = mountRepeater([{ number: '1' }], { min: 1, max: 1 });

    expect(wrapper.find('button[aria-label="Quitar"]').attributes('disabled')).toBeDefined();
    expect(wrapper.findAll('button').at(-1)!.attributes('disabled')).toBeDefined();
  });
});
