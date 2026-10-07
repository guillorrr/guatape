import { flushPromises } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import AutoComplete from 'primevue/autocomplete';
import AppRemoteSelect, { type RemoteOption } from '@/molecules/AppRemoteSelect.vue';
import { mountWithPrime } from '@/test/mount';

const ana: RemoteOption = { value: 1, label: 'Ana' };
const bruno: RemoteOption = { value: 2, label: 'Bruno' };

describe('AppRemoteSelect', () => {
  it('binds option values, showing labels from initialOptions', () => {
    const wrapper = mountWithPrime(AppRemoteSelect, {
      props: { modelValue: 1, fetchOptions: vi.fn(), initialOptions: [ana] },
    });

    expect(wrapper.findComponent(AutoComplete).props('modelValue')).toEqual(ana);
  });

  it('emits the value of the picked option', async () => {
    const wrapper = mountWithPrime(AppRemoteSelect, {
      props: { modelValue: null, fetchOptions: vi.fn() },
    });

    wrapper.findComponent(AutoComplete).vm.$emit('update:modelValue', bruno);

    expect(wrapper.emitted('update:modelValue')![0]).toEqual([2]);
  });

  it('ignores a slower, older response', async () => {
    let resolveFirst!: (o: RemoteOption[]) => void;
    const fetchOptions = vi
      .fn()
      .mockImplementationOnce(() => new Promise((r) => (resolveFirst = r)))
      .mockResolvedValueOnce([bruno]);
    const wrapper = mountWithPrime(AppRemoteSelect, { props: { modelValue: null, fetchOptions } });
    const auto = wrapper.findComponent(AutoComplete);

    auto.vm.$emit('complete', { query: 'a' });
    auto.vm.$emit('complete', { query: 'br' });
    await flushPromises();
    resolveFirst([ana]);
    await flushPromises();

    expect(auto.props('suggestions')).toEqual([bruno]);
  });

  it('passes params and clears the selection when they change', async () => {
    const fetchOptions = vi.fn().mockResolvedValue([]);
    const wrapper = mountWithPrime(AppRemoteSelect, {
      props: { modelValue: 1, fetchOptions, params: { role: 'admin' } },
    });

    wrapper.findComponent(AutoComplete).vm.$emit('complete', { query: '' });
    expect(fetchOptions).toHaveBeenCalledWith('', { role: 'admin' });

    await wrapper.setProps({ params: { role: 'member' } });

    expect(wrapper.emitted('update:modelValue')![0]).toEqual([null]);
  });
});
