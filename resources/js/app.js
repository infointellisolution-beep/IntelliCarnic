import './bootstrap';

document.addEventListener('DOMContentLoaded', () => {
	const modal = document.getElementById('articulo-modal');
	const modalTitle = document.getElementById('articulo-modal-title');
	const modalForm = document.getElementById('articulo-modal-form');
	const modalMethod = document.getElementById('articulo-modal-method');
	const modalId = document.getElementById('articulo-modal-id');
	const openCreateButtons = document.querySelectorAll('.js-articulo-create');
	const editButtons = document.querySelectorAll('.js-articulo-edit');
	const closeButtons = document.querySelectorAll('.js-articulo-modal-close');

	if (!modal || !modalForm || !modalTitle || !modalMethod || !modalId) {
		return;
	}

	const openModal = () => {
		modal.classList.add('is-open');
		modal.setAttribute('aria-hidden', 'false');
		document.body.classList.add('modal-open');
	};

	const closeModal = () => {
		modal.classList.remove('is-open');
		modal.setAttribute('aria-hidden', 'true');
		document.body.classList.remove('modal-open');
	};

	const setFieldValue = (name, value) => {
		const field = modalForm.querySelector(`[name="${name}"]`);

		if (!field) {
			return;
		}

		field.value = value ?? '';
	};

	const resetToCreateMode = () => {
		modalTitle.textContent = 'Nuevo artículo';
		modalForm.action = openCreateButtons[0]?.dataset.modalAction || modalForm.action;
		modalId.value = '';
		modalMethod.value = '';
		modalMethod.disabled = true;

		['codigo', 'descripcion', 'categoria', 'precio_sin_iva', 'iva', 'pvp', 'stock', 'estado'].forEach((fieldName) => {
			const field = modalForm.querySelector(`[name="${fieldName}"]`);

			if (!field) {
				return;
			}

			if (fieldName === 'iva') {
				field.value = field.value || 21;
				return;
			}

			if (fieldName === 'stock') {
				field.value = field.value || 0;
				return;
			}

			if (fieldName === 'estado') {
				field.value = 'activo';
				return;
			}

			field.value = '';
		});
	};

	openCreateButtons.forEach((button) => {
		button.addEventListener('click', () => {
			resetToCreateMode();
			openModal();
		});
	});

	editButtons.forEach((button) => {
		button.addEventListener('click', () => {
			const articulo = JSON.parse(button.dataset.articulo || '{}');

			modalTitle.textContent = button.dataset.modalTitle || 'Editar artículo';
			modalForm.action = button.dataset.modalAction || modalForm.action;
			modalId.value = articulo.id || '';
			modalMethod.value = 'PUT';
			modalMethod.disabled = false;

			setFieldValue('codigo', articulo.codigo);
			setFieldValue('descripcion', articulo.descripcion);
			setFieldValue('categoria', articulo.categoria);
			setFieldValue('precio_sin_iva', articulo.precio_sin_iva);
			setFieldValue('iva', articulo.iva ?? 21);
			setFieldValue('pvp', articulo.pvp);
			setFieldValue('stock', articulo.stock ?? 0);
			setFieldValue('estado', articulo.estado || 'activo');

			openModal();
		});
	});

	closeButtons.forEach((button) => {
		button.addEventListener('click', closeModal);
	});

	modal.addEventListener('click', (event) => {
		if (event.target === modal) {
			closeModal();
		}
	});

	document.addEventListener('keydown', (event) => {
		if (event.key === 'Escape' && modal.classList.contains('is-open')) {
			closeModal();
		}
	});

	if (modal.dataset.open === 'true') {
		openModal();
	}
});
