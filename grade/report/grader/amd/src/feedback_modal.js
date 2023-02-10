// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Javascript module for displaying feedback in a modal window
 *
 * @module      gradereport_grader/feedback_modal
 * @copyright   2023 Kevin Percy <kevin.percy@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import ModalFactory from 'core/modal_factory';
import ajax from 'core/ajax';
import Templates from 'core/templates';

const Selectors = {
    showFeedback: '[data-action="feedback"]',
    userId: 'tr[data-uid]',
    itemId: 'td[data-itemid]'
};

/**
 * Create the modal to display the feedback.
 *
 * @param {int} userid
 * @param {int} itemid
 * @returns {Promise}
 */
const getModal = async(userid, itemid) => {
    let feedbackData = await fetchFeedback(userid, itemid);

    return ModalFactory.create({
        title: 'Feedback',
        removeOnClose: true,
        large: true
    })
    .then(modal => {
        let body = Templates.render('core_grades/feedback_modal', {
            feedbacktext: feedbackData.feedbacktext,
            user: {
                picture: feedbackData.picture,
                fullname: feedbackData.fullname,
                additionalfield: feedbackData.additionalfield,
            },
        });

        modal.setBody(body);
        modal.setTitle(feedbackData.title);
        modal.show();

        return modal;
    });
};

/**
 * Fetch the feedback data.
 *
 * @param {int} userid
 * @param {int} itemid
 * @returns {Promise}
 */
export const fetchFeedback = (userid, itemid) => {
    const request = {
        methodname: 'core_grades_get_feedback',
        args: {
            userid: userid,
            itemid: itemid,
        },
    };
    return ajax.call([request])[0];
};

/**
 * Register event listeners for the View Feedback links.
 */
const registerEventListeners = () => {
    document.addEventListener('click', e => {
        if (e.target.closest(Selectors.showFeedback)) {
            e.preventDefault();

            getModal(e.target.closest(Selectors.userId).dataset.uid, e.target.closest(Selectors.itemId).dataset.itemid);

            return;
        }
    });
};

/**
 * Initialize module
 */
export const init = () => {
    registerEventListeners();
};
