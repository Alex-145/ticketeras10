import _ from "lodash";
window._ = _;

import axios from "axios";
window.axios = axios;
window.axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";

import Echo from "laravel-echo";
import Pusher from "pusher-js";

// Requerido por el conector de Echo (Pusher protocol)
window.Pusher = Pusher;

const cfg = {
    broadcaster: "reverb",
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST ?? window.location.hostname,
    wsPort: Number(import.meta.env.VITE_REVERB_PORT ?? 8080),
    wssPort: Number(import.meta.env.VITE_REVERB_PORT ?? 443),
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME || "http") === "https",
    enabledTransports: ["ws", "wss"],

    // Autorizer con logs (útil para ver 403/419 en /broadcasting/auth)
    authorizer: (channel, options) => {
        return {
            authorize: (socketId, callback) => {
                axios
                    .post("/broadcasting/auth", {
                        channel_name: channel.name,
                        socket_id: socketId,
                    })
                    .then((res) => {
                        console.log(
                            "[Echo][auth OK]",
                            channel.name,
                            res.status
                        );
                        callback(false, res.data);
                    })
                    .catch((error) => {
                        const status = error?.response?.status;
                        const data = error?.response?.data;
                        console.error(
                            "[Echo][auth FAIL]",
                            channel.name,
                            status,
                            data || error.message
                        );
                        callback(true, error);
                    });
            },
        };
    },
};

window.Echo = new Echo(cfg);

console.groupCollapsed("%c[Echo] init", "color:#0af");
console.log("cfg", cfg);
console.log("APP_KEY", import.meta.env.VITE_REVERB_APP_KEY);
console.log(
    "HOST",
    import.meta.env.VITE_REVERB_HOST,
    "PORT",
    import.meta.env.VITE_REVERB_PORT,
    "SCHEME",
    import.meta.env.VITE_REVERB_SCHEME
);
console.groupEnd();

// ---- Conexión de bajo nivel (logs) ----
const con =
    window.Echo?.connector?.pusher?.connection ||
    window.Echo?.connector?.connection ||
    null;

if (con && con.bind) {
    con.bind("state_change", (s) =>
        console.log("[Echo][conn] state_change", s)
    );
    con.bind("connected", () =>
        console.log(
            "[Echo][conn] connected. socket_id:",
            window.Echo.socketId?.()
        )
    );
    con.bind("disconnected", () => console.warn("[Echo][conn] disconnected"));
    con.bind("error", (e) => console.error("[Echo][conn] error", e));
} else {
    console.warn("[Echo] no connection object found (connector)");
}

// ---- Helpers de consola ----
window.debugEcho = {
    cfg,
    echo: () => window.Echo,
    socketId: () => (window.Echo?.socketId ? window.Echo.socketId() : null),
    watchPrivate(channel = "tickets.1", event = "TicketMessageCreated") {
        const ch = window.Echo.private(channel)
            .subscribed(() => console.log(`[Echo][${channel}] subscribed`))
            .error((e) => console.error(`[Echo][${channel}] error`, e))
            .listen(`.${event}`, (payload) =>
                console.log(`[Echo][${channel}] .${event}`, payload)
            );
        return ch;
    },
    async authProbe() {
        try {
            const res = await fetch("/broadcasting/auth", {
                method: "POST",
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    "X-CSRF-TOKEN":
                        document.querySelector('meta[name="csrf-token"]')
                            ?.content ?? "",
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({ channel_name: "private-tickets.test" }),
            });
            console.log("[Echo][authProbe] status", res.status, "ok:", res.ok);
            if (!res.ok) console.warn(await res.text());
        } catch (e) {
            console.error("[Echo][authProbe] error", e);
        }
    },
};
