import axios from "axios"
import {config} from "dotenv";

config({ path: '/var/www/maiven-bot/.env' });

/**
 * update server status alert
 *
 * @param {Boolean} isUp
 */
const updateStatus = async (isUp = true) => {
    try {
        await axios.post(`https://api.telegram.org/bot${process.env.TG_BOT_TOKEN}/editMessageText`, {
            chat_id: process.env.ADMIN_CHAT_ID,
            message_id: process.env.STATUS_MSG_ID,
            text: (isUp ? '🟢' : '🔴') + ' server is ' + (isUp ? 'up' : 'down')
        });
    } catch ({response: {data}}) {
        if (!data?.description.match(/(message is not modified)/)) {
            console.log('[-]', 'error:', (data || 'Something went wrong!'));
        }
    }
};

/**
 * check server health
 */
const checkUrl = async () => {
    try {
        await axios.head(process.env.MEDIA_URL);
        return true;
    } catch (error) { }
    return false;
};

/** run task hour */
await updateStatus(await checkUrl());
