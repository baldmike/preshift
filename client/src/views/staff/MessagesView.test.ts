/**
 * MessagesView.test.ts
 *
 * Tests for the MessagesView component which provides a two-tab layout
 * for Board (bulletin board) and Direct (private DM) messaging.
 *
 * Verifies:
 *   1. The component renders without crashing and displays the page heading.
 *   2. The "Corner!" back link points to /dashboard.
 *   3. Both tab buttons ("Board" and "Direct") are rendered.
 *   4. The Board tab is active by default when no query param is set.
 *   5. The BoardTab child component renders when the board tab is active.
 */

import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { ref, reactive } from 'vue'
import { setActivePinia, createPinia } from 'pinia'
import MessagesView from '@/views/staff/MessagesView.vue'

// ── Mock vue-router ─────────────────────────────────────────────────────────

/** Reactive route object so the component's computed properties update */
let mockQuery: Record<string, string> = {}

vi.mock('vue-router', () => ({
  useRoute: () => reactive({
    path: '/messages',
    query: mockQuery,
  }),
  useRouter: () => ({
    replace: vi.fn((to: any) => {
      Object.assign(mockQuery, to.query || {})
    }),
  }),
}))

// ── Mock composables ────────────────────────────────────────────────────────

vi.mock('@/composables/useApi', () => ({
  default: {
    get: vi.fn().mockImplementation((url: string) => {
      if (url === '/api/conversations/unread-count') {
        return Promise.resolve({ data: { unread_count: 0 } })
      }
      if (url === '/api/board-messages') {
        return Promise.resolve({ data: [] })
      }
      return Promise.resolve({ data: [] })
    }),
    post: vi.fn().mockResolvedValue({ data: {} }),
    patch: vi.fn(),
    delete: vi.fn(),
  },
}))

// ── Stubs ───────────────────────────────────────────────────────────────────

const routerLinkStub = {
  template: '<a :href="to" class="router-link"><slot /></a>',
  props: ['to'],
}

function mountView() {
  return mount(MessagesView, {
    global: {
      stubs: {
        'router-link': routerLinkStub,
        AppShell: { template: '<div><slot /></div>' },
        BoardTab: { template: '<div class="board-tab-stub">Board Content</div>' },
        DirectTab: { template: '<div class="direct-tab-stub">Direct Content</div>' },
        Teleport: true,
      },
    },
  })
}

// ── Tests ───────────────────────────────────────────────────────────────────

describe('MessagesView', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    mockQuery = {}
  })

  /**
   * The page heading "Messages" must always be visible to orient the user.
   */
  it('renders the page heading', async () => {
    const wrapper = mountView()
    await flushPromises()

    expect(wrapper.text()).toContain('Messages')
  })

  /**
   * The "Corner!" back link must point to /dashboard for navigation.
   */
  it('renders the Corner! back link to /dashboard', async () => {
    const wrapper = mountView()
    await flushPromises()

    const links = wrapper.findAll('a.router-link')
    const hrefs = links.map(l => l.attributes('href'))
    expect(hrefs).toContain('/dashboard')
  })

  /**
   * Both tab buttons must be rendered so users can switch between
   * the Board (bulletin board) and Direct (DM) tabs.
   */
  it('renders both Board and Direct tab buttons', async () => {
    const wrapper = mountView()
    await flushPromises()

    const buttons = wrapper.findAll('button')
    const labels = buttons.map(b => b.text())

    expect(labels.some(l => l.includes('Board'))).toBe(true)
    expect(labels.some(l => l.includes('Direct'))).toBe(true)
  })

  /**
   * When no tab query param is set, the Board tab should be active
   * by default and the BoardTab component should render.
   */
  it('defaults to the Board tab when no query param is set', async () => {
    const wrapper = mountView()
    await flushPromises()

    const boardStub = wrapper.find('.board-tab-stub')
    expect(boardStub.exists()).toBe(true)

    const directStub = wrapper.find('.direct-tab-stub')
    expect(directStub.exists()).toBe(false)
  })

  /**
   * When the tab query param is "direct", the Direct tab should render
   * instead of the Board tab.
   */
  it('renders the Direct tab when query param tab=direct', async () => {
    mockQuery = { tab: 'direct' }
    const wrapper = mountView()
    await flushPromises()

    const directStub = wrapper.find('.direct-tab-stub')
    expect(directStub.exists()).toBe(true)

    const boardStub = wrapper.find('.board-tab-stub')
    expect(boardStub.exists()).toBe(false)
  })
})
