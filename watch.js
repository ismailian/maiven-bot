import axios from "axios"
import cron from "node-cron";
import { config } from "dotenv";

config();

/**
 * update server status alert
 * 
 * @param {Boolean} isUp 
 */
const updateStatus = async (isUp = true) => {
    try {
        await axios.post(`https://api.telegram.org/bot${process.env.TG_BOT_TOKEN}/editMessage`, {
            chat_id: process.env.ADMIN_CHAT_ID,
            message_id: process.env.STATUS_MSG_ID,
            text: 'server is ' + (isUp ? 'up' : 'down')
        });
    } catch (error) { }
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

/**
 * run task hour
 * 
 * @returns 
 */
cron.schedule(
    '0 */1 * * *',
    async () => await updateStatus(
        await checkUrl()
    )
);
