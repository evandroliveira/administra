import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap-icons/font/bootstrap-icons.css';
import './bootstrap';
import * as bootstrap from 'bootstrap';
import Alpine from 'alpinejs';

window.bootstrap = bootstrap;
window.Alpine = Alpine;

const initClienteCepLookup = () => {
	document.querySelectorAll('[data-cep-form]').forEach((form) => {
		if (form.dataset.cepReady === 'true') {
			return;
		}

		const cepInput = form.querySelector('[data-cep-input]');
		const enderecoInput = form.querySelector('[data-cep-endereco]');
		const bairroInput = form.querySelector('[data-cep-bairro]');
		const cidadeInput = form.querySelector('[data-cep-cidade]');
		const estadoInput = form.querySelector('[data-cep-estado]');
		const status = form.querySelector('[data-cep-status]');

		if (!cepInput || !enderecoInput || !bairroInput || !cidadeInput || !estadoInput || !status) {
			return;
		}

		form.dataset.cepReady = 'true';

		let pendingCep = '';
		let resolvedCep = '';
		let controller;

		const formatCep = (value) => {
			const digits = value.replace(/\D/g, '').slice(0, 8);

			if (digits.length <= 5) {
				return digits;
			}

			return `${digits.slice(0, 5)}-${digits.slice(5)}`;
		};

		const setStatus = (message, tone = 'muted') => {
			status.textContent = message;
			status.className = 'form-text';

			if (tone === 'success') {
				status.classList.add('text-success');
			}

			if (tone === 'danger') {
				status.classList.add('text-danger');
			}
		};

		const fillAddress = (payload) => {
			enderecoInput.value = payload.logradouro || enderecoInput.value;
			bairroInput.value = payload.bairro || bairroInput.value;
			cidadeInput.value = payload.localidade || cidadeInput.value;
			estadoInput.value = (payload.uf || estadoInput.value || '').toUpperCase();
		};

		const lookupCep = async (digits) => {
			if (digits.length !== 8 || digits === pendingCep || digits === resolvedCep) {
				return;
			}

			pendingCep = digits;

			if (controller) {
				controller.abort();
			}

			controller = new AbortController();
			setStatus('Buscando CEP...');

			try {
				const response = await fetch(`https://viacep.com.br/ws/${digits}/json/`, {
					headers: {
						Accept: 'application/json',
					},
					signal: controller.signal,
				});

				if (!response.ok) {
					throw new Error('lookup_failed');
				}

				const payload = await response.json();

				if (payload.erro) {
					resolvedCep = '';
					setStatus('CEP nao encontrado.', 'danger');
					return;
				}

				fillAddress(payload);
				resolvedCep = digits;
				setStatus('Endereco preenchido automaticamente pelo CEP.', 'success');
			} catch (error) {
				if (error.name === 'AbortError') {
					return;
				}

				resolvedCep = '';
				setStatus('Nao foi possivel consultar o CEP agora.', 'danger');
			} finally {
				pendingCep = '';
			}
		};

		const handleCepInput = () => {
			const digits = cepInput.value.replace(/\D/g, '').slice(0, 8);
			cepInput.value = formatCep(digits);

			if (digits.length < 8) {
				resolvedCep = '';
				setStatus('');
				return;
			}

			lookupCep(digits);
		};

		cepInput.value = formatCep(cepInput.value);
		cepInput.addEventListener('input', handleCepInput);
		cepInput.addEventListener('blur', handleCepInput);
	});
};

initClienteCepLookup();

let pendingInstallPrompt;
const installAppButton = document.querySelector('[data-install-app]');

if ('serviceWorker' in navigator) {

	navigator.serviceWorker.register('/sw.js', {
		updateViaCache: 'none',
	}).catch(() => {});
}

window.addEventListener('beforeinstallprompt', (event) => {
	event.preventDefault();
	pendingInstallPrompt = event;
	installAppButton?.classList.remove('d-none');
});

installAppButton?.addEventListener('click', async () => {
	if (!pendingInstallPrompt) {
		return;
	}

	pendingInstallPrompt.prompt();
	await pendingInstallPrompt.userChoice;
	pendingInstallPrompt = null;
	installAppButton.classList.add('d-none');
});

window.addEventListener('appinstalled', () => {
	pendingInstallPrompt = null;
	installAppButton?.classList.add('d-none');
});

Alpine.start();
