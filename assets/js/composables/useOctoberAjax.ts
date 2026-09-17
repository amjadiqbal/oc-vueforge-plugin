/**
 * Typed wrapper around October CMS v4.2+'s "Larajax" AJAX framework, exposed
 * globally as `window.jax` (this replaced the old jQuery `.request()`/`oc.ajax`
 * API - see docs/VUE3_MIGRATION_GUIDE.md item #3). Confirmed against
 * `vendor/larajax/larajax/resources/types/index.d.ts` shipped with October 4.2.
 */

export interface OctoberAjaxOptions {
    /** Extra POST data to send alongside the handler call. */
    data?: Record<string, unknown>;
    /** A confirmation prompt shown before the request fires. */
    confirm?: string;
    /** Show a flash message with the response's `X_OCTOBER_ERROR_MESSAGE` / success text. */
    flash?: boolean;
    /** Partial(s) to update: { 'partial-name': '#target-selector' }. */
    update?: Record<string, string>;
    /** A loading indicator selector/element to toggle during the request. */
    loading?: string | HTMLElement;
}

export interface OctoberAjaxResult<T = Record<string, unknown>> {
    data: T;
}

interface JaxGlobal {
    ajax<T = Record<string, unknown>>(
        handler: string,
        options?: Record<string, unknown>
    ): Promise<T>;
}

function getJax(): JaxGlobal {
    const jax = (window as unknown as { jax?: JaxGlobal }).jax;

    if (!jax) {
        throw new Error(
            'VueForge: window.jax is not available. useOctoberAjax() must be called from within an October CMS backend page where the Larajax framework has loaded.'
        );
    }

    return jax;
}

/**
 * useOctoberAjax returns a `call()` function that performs a Promise-based
 * October AJAX handler request, mirroring the data-request attributes
 * (`data-request`, `data-request-data`, `data-request-confirm`,
 * `data-request-flash`) available in Twig/PHP markup.
 */
export function useOctoberAjax() {
    async function call<T = Record<string, unknown>>(
        handler: string,
        options: OctoberAjaxOptions = {}
    ): Promise<OctoberAjaxResult<T>> {
        if (options.confirm && !window.confirm(options.confirm)) {
            return Promise.reject(new Error('cancelled'));
        }

        const jax = getJax();

        const data = (await jax.ajax<T>(handler, {
            data: options.data,
            flash: options.flash ?? false,
            update: options.update,
            loading: options.loading,
        })) as T;

        return { data };
    }

    return { call };
}
