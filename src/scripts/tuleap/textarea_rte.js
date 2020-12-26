/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import "regenerator-runtime/runtime";

/* global Class:readonly Builder:readonly $:readonly */
var tuleap = window.tuleap || {};
tuleap.textarea = tuleap.textarea || {};
tuleap.private_access = Boolean(tuleap.private_access);

import "../codendi/RichTextEditor.js";

tuleap.textarea.RTE = Class.create(window.codendi.RTE, {
    initialize: function ($super, element, options) {
        options = Object.extend({ toolbar: "tuleap" }, options || {});
        this.options = Object.extend({ htmlFormat: false, id: 0 }, options || {});
        $super(element, options);
        // This div contains comment format selection buttons and checkbox private comments
        var div = Builder.node("div");
        var header = Builder.node("div", { class: "rte_header" });
        var select_container = Builder.node("div", { class: "rte_format" });

        select_container.appendChild(document.createTextNode("Format : "));
        header.appendChild(select_container);
        div.appendChild(header);

        if (element.id === "tracker_followup_comment_new" && tuleap.private_access) {
            const private_container = Builder.node("div", { class: "rte_private" });
            private_container.appendChild(document.createTextNode("Private comment : "));
            header.appendChild(private_container);
            const checkbox = Builder.node("input", {
                type: "checkbox",
                id: "private_comment_input" + this.options.id,
                name: "private_comment_input" + this.options.id,
                class: "checkbox",
            });
            private_container.appendChild(checkbox);
            checkbox.addEventListener("change", () => {
                element.classList.toggle("comment-body__private");

                /*const rte_body = window.CKEDITOR.instances[element.id].document.getBody().$;*/

                // if (window.CKEDITOR.instances[this.element.id] && this.options.privateFormat) {
                //     rte_body.style.backgroundColor = "#dcede4";
                // } else if (window.CKEDITOR.instances[this.element.id] && !this.options.privateFormat) {
                //     rte_body.style.backgroundColor = "";
                // }
            });
        }

        if (this.options.privateFormat) {
            element.classList.toggle("comment-body__private");
            // const rte_body = window.CKEDITOR.instances[element.id].document.getBody().$;
            //
            // // if (window.CKEDITOR.instances[this.element.id] && this.options.privateFormat) {
            // //     rte_body.style.backgroundColor = "#dcede4";
            // // } else if (window.CKEDITOR.instances[this.element.id] && !this.options.privateFormat) {
            // //     rte_body.style.backgroundColor = "";
            // // }
        }

        var div_clear = Builder.node("div", { class: "rte_clear" });
        div.appendChild(div_clear);

        if (undefined == this.options.name) {
            this.options.name = "comment_format" + this.options.id;
        }

        var selectbox = Builder.node("select", {
            id: "rte_format_selectbox" + this.options.id,
            name: this.options.name,
            class: "input-small",
        });
        select_container.appendChild(selectbox);

        var text_value = "text";
        var html_value = "html";

        if (element.id === "tracker_artifact_comment") {
            text_value = "0";
            html_value = "1";
        }

        // Add an option that tells that the content format is text
        // The value is defined in Artifact class.
        var text_option = Builder.node(
            "option",
            { value: text_value, id: "comment_format_text" + this.options.id },
            "Text"
        );
        selectbox.appendChild(text_option);

        this.help_block = null;
        if (typeof this.element.dataset.helpId !== "undefined") {
            this.help_block = document.getElementById(this.element.dataset.helpId);
        }

        // Add an option that tells that the content format is HTML
        // The value is defined in Artifact class.
        var html_option = Builder.node(
            "option",
            { value: html_value, id: "comment_format_html" + this.options.id },
            "HTML"
        );
        selectbox.appendChild(html_option);

        Element.insert(this.element, { before: div });

        div.appendChild(this.element);

        if (this.options.htmlFormat == true) {
            selectbox.selectedIndex = 1;
            html_option.selected = true;
            text_option.selected = false;
        } else {
            selectbox.selectedIndex = 0;
            html_option.selected = false;
            text_option.selected = true;
        }

        if ($("comment_format_html" + this.options.id).selected == true) {
            if (this.help_block) {
                this.help_block.classList.add("shown");
            }
            this.init_rte();
        }

        if (this.options.toggle) {
            selectbox.observe("change", this.toggle.bindAsEventListener(this, selectbox));
        }
    },

    toggle: function ($super, event, selectbox) {
        var option = selectbox.options[selectbox.selectedIndex].value,
            id = this.element.id;

        if (option === "0") {
            option = "text";
        } else if (option === "1") {
            option = "html";
        }

        if (this.help_block) {
            this.help_block.classList.toggle("shown");
        }

        if ($(id).hasAttribute("data-required") && option == "text" && this.rte) {
            $(id).removeAttribute("data-required");
            $(id).writeAttribute("required", true);
        }

        $super(event, option);
    },

    init_rte: function ($super) {
        var id = this.element.id;

        $super();

        (function recordRequiredAttribute() {
            if ($(id).hasAttribute("required")) {
                $(id).removeAttribute("required");
                $(id).writeAttribute("data-required", true);
            }
        })();

        if (window.CKEDITOR.instances[this.element.id]) {
            window.CKEDITOR.instances[this.element.id].addCss("body { background-color: red; }");
        }

        //const rte_body = window.CKEDITOR.instances[this.element.id].document.getBody().$;

        // if (window.CKEDITOR.instances[this.element.id] && this.options.privateFormat) {
        //     rte_body.style.backgroundColor = "#dcede4";
        // } else if (window.CKEDITOR.instances[this.element.id] && !this.options.privateFormat) {
        //     rte_body.style.backgroundColor = "";
        // }
    },
});
