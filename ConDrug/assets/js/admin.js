(function () {
    const ready = (callback) => {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback);
        } else {
            callback();
        }
    };

    const message = 'ConDrug admin script loaded. Ready for future enhancements.';
    // eslint-disable-next-line no-console
    console.info(message);

    const bindRemoveHandler = (button) => {
        if (!button) {
            return;
        }

        button.addEventListener('click', (event) => {
            event.preventDefault();
            const row = button.closest('.condrug-feature-row');
            if (row) {
                row.remove();
            }
        });
    };

    const createFeatureRow = (plan) => {
        const wrapper = document.createElement('div');
        wrapper.className = 'condrug-feature-row';

        const input = document.createElement('input');
        input.type = 'text';
        input.name = `plan_${plan}_features[]`;
        input.className = 'regular-text';

        const removeButton = document.createElement('button');
        removeButton.type = 'button';
        removeButton.className = 'button condrug-feature-remove';
        removeButton.textContent = (typeof adminLocalization !== 'undefined' && adminLocalization.removeText) ? adminLocalization.removeText : 'Remove';

        bindRemoveHandler(removeButton);

        wrapper.appendChild(input);
        wrapper.appendChild(removeButton);
        return wrapper;
    };

    ready(() => {
        document.querySelectorAll('.condrug-feature-add').forEach((button) => {
            button.addEventListener('click', (event) => {
                event.preventDefault();
                const plan = button.dataset.plan;
                const list = document.querySelector(`.condrug-features-list[data-plan="${plan}"]`);
                if (!list) {
                    return;
                }

                const row = createFeatureRow(plan);
                list.appendChild(row);
            });
        });

        document.querySelectorAll('.condrug-feature-remove').forEach(bindRemoveHandler);
    });
})();
