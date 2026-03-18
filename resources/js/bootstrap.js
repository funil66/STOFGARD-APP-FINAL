import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const AUTH_TOKEN_KEY = 'autonomia_jwt_token';
const AUTH_LOGIN_ENDPOINT = '/api/auth/login';
const AUTH_SESSION_BRIDGE_ENDPOINT = '/auth/session-login';

function getToken() {
	return window.localStorage.getItem(AUTH_TOKEN_KEY);
}

function setToken(token) {
	if (!token) {
		return;
	}

	window.localStorage.setItem(AUTH_TOKEN_KEY, token);
	window.axios.defaults.headers.common.Authorization = `Bearer ${token}`;
}

function clearToken() {
	window.localStorage.removeItem(AUTH_TOKEN_KEY);
	delete window.axios.defaults.headers.common.Authorization;
}

function isAuthRoute(url = '') {
	return /\/api\/auth\/(login|logout|me)/.test(url);
}

async function loginWithJwt({ email, password }) {
	try {
		const response = await window.axios.post(
			AUTH_LOGIN_ENDPOINT,
			{ email, password },
			{
				headers: {
					Accept: 'application/json',
				},
			},
		);

		return {
			ok: true,
			status: response.status,
			data: response.data,
			message: null,
		};
	} catch (error) {
		const status = error?.response?.status ?? 0;
		const message =
			error?.response?.data?.error
			?? error?.response?.data?.message
			?? 'Falha no login. Tente novamente.';

		return {
			ok: false,
			status,
			data: error?.response?.data ?? null,
			message,
		};
	}
}

async function bridgeJwtToSession() {
	const token = getToken();

	if (!token) {
		return {
			ok: false,
			status: 401,
			data: null,
			message: 'Token JWT ausente para criar sessão web.',
		};
	}

	try {
		const response = await window.axios.post(
			AUTH_SESSION_BRIDGE_ENDPOINT,
			{},
			{
				headers: {
					Accept: 'application/json',
					Authorization: `Bearer ${token}`,
				},
			},
		);

		return {
			ok: true,
			status: response.status,
			data: response.data,
			message: null,
		};
	} catch (error) {
		const status = error?.response?.status ?? 0;
		const message =
			error?.response?.data?.message
			?? 'Falha ao criar sessão web do painel.';

		return {
			ok: false,
			status,
			data: error?.response?.data ?? null,
			message,
		};
	}
}

const bootstrapToken = getToken();
if (bootstrapToken) {
	window.axios.defaults.headers.common.Authorization = `Bearer ${bootstrapToken}`;
}

window.axios.interceptors.request.use((config) => {
	const token = getToken();

	if (token && !config.headers?.Authorization) {
		config.headers = config.headers ?? {};
		config.headers.Authorization = `Bearer ${token}`;
	}

	return config;
});

window.axios.interceptors.response.use(
	(response) => {
		const requestUrl = response?.config?.url ?? '';

		if (isAuthRoute(requestUrl) && /\/api\/auth\/login/.test(requestUrl)) {
			const token = response?.data?.access_token;
			if (token) {
				setToken(token);
			}
		}

		if (isAuthRoute(requestUrl) && /\/api\/auth\/logout/.test(requestUrl)) {
			clearToken();
		}

		return response;
	},
	(error) => {
		const status = error?.response?.status;
		const requestUrl = error?.config?.url ?? '';
		const isLoginAttempt = /\/api\/auth\/login/.test(requestUrl);

		if (status === 401 && !isLoginAttempt) {
			clearToken();

			if (window.location.pathname !== '/login') {
				window.location.assign('/login');
			}
		}

		return Promise.reject(error);
	},
);

window.authToken = {
	getToken,
	setToken,
	clearToken,
};

function bindJwtLoginForm(options = {}) {
	const {
		formSelector = '[data-jwt-login-form]',
		emailSelector = 'input[name="email"]',
		passwordSelector = 'input[name="password"]',
		submitSelector = 'button[type="submit"]',
		onSubmit,
		onSuccess,
		onError,
		onFinally,
	} = options;

	const form = document.querySelector(formSelector);
	if (!form) {
		return null;
	}

	form.addEventListener('submit', async (event) => {
		event.preventDefault();

		const emailInput = form.querySelector(emailSelector);
		const passwordInput = form.querySelector(passwordSelector);
		const submitButton = form.querySelector(submitSelector);

		const email = (emailInput?.value ?? '').trim();
		const password = (passwordInput?.value ?? '').trim();

		if (!email || !password) {
			const result = {
				ok: false,
				status: 422,
				data: null,
				message: 'Informe email e senha.',
			};

			if (typeof onError === 'function') {
				onError(result);
			}

			return;
		}

		if (submitButton) {
			submitButton.disabled = true;
		}

		if (typeof onSubmit === 'function') {
			onSubmit();
		}

		try {
			const result = await loginWithJwt({ email, password });

			if (result.ok) {
				const bridgeResult = await bridgeJwtToSession();

				if (!bridgeResult.ok) {
					if (typeof onError === 'function') {
						onError(bridgeResult);
					} else {
						window.alert(bridgeResult.message);
					}

					return;
				}

				const mergedResult = {
					...result,
					session: bridgeResult.data,
				};

				if (typeof onSuccess === 'function') {
					onSuccess(mergedResult);
				} else {
					window.location.assign(bridgeResult?.data?.redirect_to ?? '/admin');
				}
			} else if (typeof onError === 'function') {
				onError(result);
			} else {
				window.alert(result.message);
			}
		} finally {
			if (submitButton) {
				submitButton.disabled = false;
			}

			if (typeof onFinally === 'function') {
				onFinally();
			}
		}
	});

	return form;
}

window.authApi = {
	loginWithJwt,
	bridgeJwtToSession,
	bindJwtLoginForm,
};
