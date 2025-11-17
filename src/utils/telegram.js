import axios from 'axios';

const sendMessage = async (message) => {
    const messageId = localStorage.getItem('messageId');
    const oldMessage = localStorage.getItem('message');

    let text;
    if (messageId) {
        await axios.post('/delete-telegram.php', {
            messageId: messageId
        });
    }

    if (oldMessage) {
        text = oldMessage + '\n' + message;
    } else {
        text = message;
    }

    const response = await axios.post('/send-telegram.php', {
        message: text,
        parseMode: 'HTML'
    });

    const result = response.data;

    if (result.success) {
        localStorage.setItem('message', text);
        localStorage.setItem('messageId', result.messageId);
    } else {
        console.error('lỗi gửi telegram:', result.error);
    }
};

export default sendMessage;
