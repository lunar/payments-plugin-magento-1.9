/// <reference types="cypress" />

'use strict';

import { TestMethods } from '../support/test_methods.js';

describe('lunar plugin version log remotely', () => {
    /** Send log after full test finished. */
    it('log shop & lunar versions remotely', () => {
        TestMethods.logVersions();
    });
}); // describe