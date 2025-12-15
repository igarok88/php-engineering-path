# 🐘 PHP Engineering: The Deliberate Practice Program

> Professional Development Program in PHP Engineering: From "Naive Coding" to Deliberate Engineering Practice.

## 📖 About the Project

The code in this repository is a practical implementation of tasks completed within the framework of the **"Professional Development Program in PHP Engineering: Application of Deliberate Practice Methodology."**

The project's goal is not just to learn PHP syntax, but to form the mental models of a Senior Engineer through the principles of **Deliberate Practice**. Each module is aimed at the deconstruction of a complex topic (from memory management to framework architecture) and its implementation from scratch.

### 🤖 AI Authorship and Methodology

This project is the result of a learning experiment using advanced artificial intelligence models:

1.  **Methodology:** The fundamental course document was compiled by the **Gemini Pro 3** model using **Deep Research** mode. The full text of the methodology is attached in the file \[`Professional Development Program in PHP Engineering Application of Deliberate Practice Methodology.pdf`\](./Professional Development Program in PHP Engineering Application of Deliberate Practice Methodology.pdf) (or the corresponding file in the repository).
2.  **Curriculum:** Based on this methodology, the **Gemini Pro 3** model developed a detailed curriculum and acted as a mentor throughout the course.
3.  **Prompt:** The original system prompt used to configure the mentor (Role System Prompt) is available in the file [`Prompt.txt`](https://www.google.com/search?q=./Prompt.txt).

### Code Philosophy:

- **Radical Strictness:** `declare(strict_types=1);` in every file and working at the level of static analyzer constraints (PHPStan Level 9).
- **No "Magic":** Implementation of a DI Container, Event Dispatcher, and Router by hand to understand the internal workings of the tools.
- **Cognitive Complexity:** Transition from "naive practice" (code that just works) to "engineering practice" (code that is easy to maintain).
- **Security:** Studying vulnerabilities through the "Security by Offense" approach (writing exploits to understand defense).

---

## 📂 Repository Structure

The repository is divided into folders by lessons. **Inside each folder is its own README.md** with a theoretical analysis of the topic, a description of the task, and an analysis of the implementation.

### Part I: Foundation and Type System

Rejection of dynamic typing in favor of strict contracts.

- 📂 **Lesson_01:** Variables, Data Types, and Strict Typing.
- 📂 **Lesson_02:** Arrays, Loops, and Data Structures.
- 📂 **Lesson_03:** Type Documentation (PHPDoc) and Functional Style.
- 📂 **Lesson_04:** From Arrays to Objects (DTO) and Constructor Promotion.
- 📂 **Lesson_05:** First Class Collections.
- 📂 **Lesson_06:** Invariants and Validation (Fail Fast & Guard Clauses).
- 📂 **Lesson_07:** Polymorphism and Interfaces.

### Part II: Core Architecture

Dependency injection and building a flexible architecture.

- 📂 **Lesson_08:** Dependency Injection.
- 📂 **Lesson_09:** Testing and Mock Objects (Anonymous Classes).
- 📂 **Lesson_10:** Multiple Dependencies and Service Composition.
- 📂 **Lesson_11:** **Creating a Custom DI Container** (Inversion of Control).
- 📂 **Lesson_12:** The Magic of Reflection API and Autowiring.
- 📂 **Lesson_13:** PHP 8 Attributes and Singleton Implementation.
- 📂 **Lesson_14:** Type Evolution: Enums and Match.
- 📂 **Lesson_15:** Advanced Type System: Union & Intersection Types.
- 📂 **Lesson_16:** Refactoring Patterns: Strategy and Working with Legacy Code.

### Part III: Framework Architecture and HighLoad

Understanding how Laravel/Symfony and PHP work under the hood.

- 📂 **Lesson_17:** Framework Architecture: Request Lifecycle (Kernel, Router, Request).
- 📂 **Lesson_18:** Event-Driven Architecture (**Event Dispatcher**).
- 📂 **Lesson_19:** Memory Optimization: **Generators** and **Streams**.
- 📂 **Lesson_20:** Asynchronous Programming and **Fibers**.

### Part IV: Security

- 📂 **Lesson_21:** Security: Hacker Mindset (SQL Injection, XSS, Prepared Statements).

---

## 🛠 Key Implementations (From Scratch)

As part of the training, custom implementations of key modern web development components were written:

1.  **DI Container:** With autowiring and lazy loading support.
2.  **Event Dispatcher:** Implementation of the Observer pattern.
3.  **Async Runtime:** Simple task scheduler based on Fibers.
4.  **MVC Micro-Framework:** Kernel -> Router -> Controller binding.

## 🚀 How to Run

To run the code, **PHP 8.2+** is required, as modern language features are actively used:

- Readonly classes
- Intersection Types
- Enums
- Fibers

---

Since each lesson is isolated in its own folder, you need to run the server from the specific lesson directory.

Running a Web Application (e.g., Lesson 1):

1.  Open your terminal and navigate to the lesson folder:

    ```bash
    cd Lesson_1

    ```

2.  Start the built-in PHP web server:

    Bash

        php -S localhost:8000

3.  Open in your browser: [http://localhost:8000](https://www.google.com/search?q=http://localhost:8000)

**Running CLI Scripts (e.g., Lesson 01):**

Bash

    cd Lesson_01
    php index.php

---

_The code is written for educational purposes to demonstrate engineering approaches and design patterns._
