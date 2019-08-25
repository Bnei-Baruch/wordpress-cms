process.env.NODE_TLS_REJECT_UNAUTHORIZED = "0";

const WPAPI = require('wpapi');
const wp = new WPAPI({
    endpoint: 'http://localhost:8080/wp-json',
    username: 'wordpress',
    password: 'NKcY 5nTQ WYFe aB4X YDiY bwFg'
});

wp.posts().create({
    title: 'Yehuda Leib Ha-Levi Ashlag (Baal HaSulam)/Letters Letter 4 (1920)',
    content: '<h1>TITLE</h1>',
    status: 'publish',
    type: 'post',
    comment_status: 'closed',
    ping_status: 'closed',
    slug: '675-trk-t-bs-igeret-04-1920-html',
    meta: {
        name: '675_eng_t_bs-igeret-04-1920.html',
        unit: 'Z1cEqLUt',
    },

}).then(function (data) {
    console.log("Created Post with ID: ", data.id);
}).catch(function (err) {
    console.error(err);
});
