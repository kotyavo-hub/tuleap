<!---
  - Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
  -
  - This file is a part of Tuleap.
  -
  - Tuleap is free software; you can redistribute it and/or modify
  - it under the terms of the GNU General Public License as published by
  - the Free Software Foundation; either version 2 of the License, or
  - (at your option) any later version.
  -
  - Tuleap is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU General Public License for more details.
  -
  - You should have received a copy of the GNU General Public License
  - along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
    <div>
        <form v-bind:action="create_new_program_increment" method="post">
            <div class="program-increment-title-with-button">
                <h2 v-translate class="program-increment-title">Program Increment</h2>
                <button
                    class="tlp-button-primary tlp-button-outline tlp-button-small program-increment-title-button"
                    v-if="can_create_program_increment"
                    data-test="create-program-increment-button"
                >
                    <i class="fas fa-plus tlp-button-icon" aria-hidden="true"></i>
                    <span v-translate>Add a program increment</span>
                </button>
            </div>
        </form>

        <empty-state
            v-if="program_increments.length === 0 && !is_loading && !has_error"
            data-test="empty-state"
        />

        <program-increment-card
            v-for="increment in program_increments"
            v-bind:key="increment.artifact_id"
            v-bind:increment="increment"
            data-test="program-increments"
        />

        <program-increment-skeleton v-if="is_loading" dat-test="program-increment-skeleton" />

        <div
            id="program-increment-error"
            class="tlp-alert-danger"
            v-if="has_error"
            data-test="program-increment-error"
        >
            {{ error_message }}
        </div>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";
import EmptyState from "./EmptyState.vue";
import ProgramIncrementCard from "./ProgramIncrementCard.vue";
import {
    getProgramIncrements,
    ProgramIncrement,
} from "../../../helpers/ProgramIncrement/program-increment-retriever";
import { programId, canCreateProgramIncrement, programIncrementId } from "../../../configuration";
import ProgramIncrementSkeleton from "./ProgramIncrementSkeleton.vue";
import { buildCreateNewProgramIncrement } from "../../../helpers/location-helper";

@Component({
    components: { ProgramIncrementSkeleton, ProgramIncrementCard, EmptyState },
})
export default class ProgramIncrementList extends Vue {
    private error_message = "";
    private has_error = false;
    private program_increments: Array<ProgramIncrement> = [];
    private is_loading = false;

    async mounted(): Promise<void> {
        try {
            this.is_loading = true;
            this.program_increments = await getProgramIncrements(programId());
        } catch (e) {
            this.has_error = true;
            this.error_message = this.$gettext(
                "The retrieval of the program increments has failed"
            );
            throw e;
        } finally {
            this.is_loading = false;
        }
    }

    get can_create_program_increment(): boolean {
        return canCreateProgramIncrement() && this.program_increments.length > 0;
    }

    get create_new_program_increment(): string {
        return buildCreateNewProgramIncrement(programIncrementId());
    }
}
</script>
