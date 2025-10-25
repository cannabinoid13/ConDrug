(function () {
    const planRoot = document.querySelector('.condrug-plan-selection');
    if (!planRoot) {
        const workspace = document.querySelector('.condrug-workspace');
        if (workspace) {
            workspace.dataset.condrugWorkspaceLoaded = 'true';
        }
        return;
    }

    const publishableKey = planRoot.dataset.publishableKey;
    const couponCode = (typeof condrugCheckout !== 'undefined' && condrugCheckout.coupon) ? condrugCheckout.coupon : '';
    const ajaxUrl = (typeof condrugCheckout !== 'undefined' && condrugCheckout.ajaxUrl) ? condrugCheckout.ajaxUrl : (window.ajaxurl || '/wp-admin/admin-ajax.php');
    const messages = (typeof condrugCheckout !== 'undefined' && condrugCheckout.messages) ? condrugCheckout.messages : { genericError: 'Unable to start checkout. Please try again or contact support.' };
    let stripe;

    const showError = (message) => {
        console.error(message);
        if (window.alert) {
            alert(message);
        }
    };

    const getStripe = () => {
        if (!publishableKey) {
            showError('Stripe publishable key missing.');
            return null;
        }
        if (!stripe) {
            stripe = Stripe(publishableKey);
        }
        return stripe;
    };

    const handleCheckout = async (button) => {
        const stripeClient = getStripe();
        if (!stripeClient) {
            return;
        }

        button.disabled = true;
        button.classList.add('is-loading');

        try {
            const response = await fetch(ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                },
                body: new URLSearchParams({
                    action: 'condrug_create_checkout',
                    plan_id: button.dataset.planId,
                    coupon_code: couponCode,
                    nonce: condrugCheckout.nonce,
                }),
            });

            const result = await response.json();
            if (!response.ok || !result.success) {
                showError(result?.data?.message || messages.genericError);
                return;
            }

            const redirectResult = await stripeClient.redirectToCheckout({ sessionId: result.data.session_id });
            if (redirectResult.error) {
                showError(redirectResult.error.message);
            }
        } catch (error) {
            showError(messages.genericError);
            console.error('Checkout failed', error);
        } finally {
            button.disabled = false;
            button.classList.remove('is-loading');
        }
    };

    document.querySelectorAll('[data-condrug-checkout]').forEach((button) => {
        button.addEventListener('click', () => handleCheckout(button));
    });
})();
