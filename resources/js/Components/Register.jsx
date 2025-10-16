import React, { useEffect, useState } from "react";
import { useNavigate, useLocation } from "react-router-dom";
// import jwt_decode from "jwt-decode";
import "../../css/Style.css";

const SignIn = () => {
  const navigate = useNavigate();
  const location = useLocation();
  const [role, setRole] = useState("Student");

  useEffect(() => {
    if (location.state?.userType) {
      setRole(location.state.userType);
    }
  }, [location.state]);

  const [signInData, setSignInData] = useState({ username: "", password: "" });
  const [signUpData, setSignUpData] = useState({
    username: "",
    email: "",
    password: "",
    role: role,
  });

  useEffect(() => {
    setSignUpData((prev) => ({
      ...prev,
      role: role,
    }));
  }, [role]);

  console.log(signUpData)

  const [verificationCode, setVerificationCode] = useState(false);
  const [message, setMessage] = useState("");
  const [verifCode, setVerifCode] = useState(["", "", "", "", "", ""]);
  const [isError, setIsError] = useState(true);

  useEffect(() => {
    const signInBtn = document.querySelector("#sign-in-btn");
    const signUpBtn = document.querySelector("#sign-up-btn");
    const container = document.querySelector(".container");

    if (signInBtn && signUpBtn && container) {
      const signUp = () => container.classList.remove("sign-up-mode");
      const signIn = () => container.classList.add("sign-up-mode");

      signUpBtn.addEventListener("click", signUp);
      signInBtn.addEventListener("click", signIn);

      return () => {
        signUpBtn.removeEventListener("click", signUp);
        signInBtn.removeEventListener("click", signIn);
      };
    }
  }, []);

  useEffect(() => {
    const loadGoogleScript = () => {
      const script = document.createElement("script");
      script.src = "https://accounts.google.com/gsi/client";
      script.async = true;
      script.defer = true;
      script.onload = initializeGoogle;
      document.body.appendChild(script);
    };

    const initializeGoogle = () => {
      if (window.google) {
        window.google.accounts.id.initialize({
          client_id:
            "797018306927-lr3vve536s6qc1prvsfjuuebg2ahaf7r.apps.googleusercontent.com",
          callback: handleCredentialResponse,
        });

        window.google.accounts.id.renderButton(
          document.getElementById("google-signin-btn"),
          { theme: "", size: "large" }
        );

        window.google.accounts.id.renderButton(
          document.getElementById("google-signup-btn"),
          { theme: "", size: "large" } 
        );

        window.google.accounts.id.prompt();
      }
    };

    const handleCredentialResponse = async (response) => {
      try {
        const res = await fetch("http://localhost:8000/auth/google-login/", {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify({ id_token: response.credential, role: role }),
        });

        if (!res.ok) throw new Error("Failed login");

        const data = await res.json();
        localStorage.setItem("token", data.token);
        navigate("/");
      } catch (err) {
        console.error("Login error", err);
        alert("Login failed.");
      }
    };

    loadGoogleScript();
  }, [role]);

  const handleSignIn = async (e) => {
    e.preventDefault();
    const response = await fetch("http://localhost:8000/auth/login/", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(signInData),
    });

    const data = await response.json();
    if (response.ok) {
      localStorage.setItem("token", data.token);
      navigate("/");
    } else {
      setIsError(true);
      setMessage(data.error || "Login failed");
      setTimeout(() => {
        setMessage("");
        setIsError(false);
      }, 5000);
    }
  };

  const handleSignUp = async (e) => {
    e.preventDefault();
    const response = await fetch("http://localhost:8000/auth/register/", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(signUpData),
    });

    const data = await response.json();
    if (response.ok) {
      setVerificationCode(true);
    } else {
      setIsError(true);
      setMessage(data.error || "Sign up failed");
      setTimeout(() => {
        setMessage("");
        setIsError(false);
      }, 5000);
    }
  };

  const verifyRegister = async (e) => {
    e.preventDefault();
    const code = verifCode.join("").trim();
    const email = signUpData.email.trim();
    console.log(email, code);

    if (code.length !== 6) {
      setMessage("Please enter a complete verification code");
      setIsError(true);
      setTimeout(() => {
        setMessage("");
        setIsError(false);
      }, 5000);
      return;
    }

    try {
      const response = await fetch(
        "http://localhost:8000/auth/verify-register/",
        {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify({
            email,
            code,
          }),
        }
      );

      const data = await response.json();
      if (response.ok) {
        localStorage.setItem("token", data.token);
        setTimeout(() => navigate("/"), 1000);
      } else {
        setMessage(data.message || "Failed to verify email.");
        setIsError(true);
        setTimeout(() => {
          setMessage("");
          setIsError(false);
        }, 5000);
      }
    } catch (error) {
      console.error("An error occurred:", error);
      setMessage("An error occurred. Please try again.");
      setIsError(true);
      setTimeout(() => {
        setMessage("");
        setIsError(false);
      }, 5000);
    }
  };

  const handleCloseCalendar = (e) => {
    if (e.target.classList.contains("modal-overlay")) {
      setVerificationCode(false);
      setVerifCode(["", "", "", "", "", ""]);
      setSignUpData({
        username: "",
        email: "",
        password: "",
        role: "student",
      });
      setMessage("");
      setIsError(false);
    }
  };

  const handleCodeChange = (index, value) => {
    if (value.length > 1) return;

    const newCode = [...verifCode];
    newCode[index] = value;
    setVerifCode(newCode);

    if (value && index < 5) {
      const nextInput = document.getElementById(`code-${index + 1}`);
      if (nextInput) nextInput.focus();
    }
  };

  const handleKeyDown = (index, e) => {
    if (e.key === "Backspace" && !verifCode[index]) {
      e.preventDefault();
      const prevInput = document.getElementById(`code-${index - 1}`);
      if (prevInput) {
        prevInput.focus();
        const newCode = [...verifCode];
        newCode[index - 1] = "";
        setVerifCode(newCode);
      }
    }
  };

  return (
    <div className="container">
      <div className="forms-container">
        <div className="signin-signup">
          <form className="sign-in-form">
            <img src="./logo.png" alt="ClassMatch" style={{width: "50px", cursor: "pointer"}} onClick={() => navigate("/")} />
            <h2 className="title">Sign In</h2>
            <div className="input-field">
              <i className="fas fa-user"></i>
              <input
                type="text"
                placeholder="Username"
                value={signInData.username}
                onChange={(e) =>
                  setSignInData({ ...signInData, username: e.target.value })
                }
              />
            </div>
            <div className="input-field">
              <i className="fas fa-lock"></i>
              <input
                type="password"
                placeholder="Password"
                value={signInData.password}
                onChange={(e) =>
                  setSignInData({ ...signInData, password: e.target.value })
                }
              />
            </div>

            <span>
              Forgot passowrd?
              <a href=" " onClick={() => navigate("/forgot-password")} style={{textDecoration: "underline", color: "#0a6b11"}}>
                {" "}
                Click-here!
              </a>
            </span>
            <input
              type="submit"
              value="Login"
              className="btn solid"
              onClick={handleSignIn}
            />
            <p className="social-text"> Or </p>
            <div id="google-signin-btn"></div>
          </form>

          <form className="sign-up-form">
                        <img src="./logo.png" alt="ClassMatch" style={{width: "50px", cursor: "pointer"}} onClick={() => navigate("/")} />
            <h2 className="title">Sign Up as {role}</h2>
            <div className="input-field">
              <i className="fas fa-user"></i>
              <input
                type="text"
                placeholder="Username"
                value={signUpData.username}
                onChange={(e) =>
                  setSignUpData({ ...signUpData, username: e.target.value })
                }
              />
            </div>
            <div className="input-field">
              <i className="fas fa-envelope"></i>
              <input
                type="email"
                placeholder="Email"
                value={signUpData.email}
                onChange={(e) =>
                  setSignUpData({ ...signUpData, email: e.target.value })
                }
              />
            </div>
            <div className="input-field">
              <i className="fas fa-lock"></i>
              <input
                type="password"
                placeholder="Password"
                value={signUpData.password}
                onChange={(e) =>
                  setSignUpData({ ...signUpData, password: e.target.value })
                }
              />
            </div>
            <input
              type="submit"
              value="SignUp"
              className="btn solid"
              onClick={handleSignUp}
            />
            <p className="social-text">Or</p>
            <div id="google-signup-btn"></div>
          </form>
        </div>
      </div>

      <div className="panels-container">
        <div className="panel left-panel">
          <div className="content">
            <h3>One of us?</h3>
            <p>If you already have an account, just sign in. We missed you!</p>
            <button className="btn transparent" id="sign-in-btn">
              Sign In
            </button>
          </div>
          <img className="image" src="/register.jpg" alt="signup" />
        </div>

        <div className="panel right-panel">
          <div className="content">
            <h3>New here?</h3>
            <p>
              Create an account to join our platform and enjoy the experience.
            </p>
            <button className="btn transparent" id="sign-up-btn">
              Sign Up
            </button>
          </div>
          <img className="image" src="/log.jpg" alt="login" />
        </div>
      </div>

      {message && (
        <div
          className="message"
          style={{
            backgroundColor: isError ? "#f8d7da" : "#d4edda",
            border: isError ? "1px solid #f5c6cb" : "1px solid #c3e6cb",
            color: isError ? "red" : "green",
          }}
        >
          <img
            src={isError ? "./warning.png" : "./done.png"}
            alt="status"
            className="message-img"
          />
          <span style={{ color: isError ? "red" : "green" }}>{message}</span>
          <div
            className="message-timer"
            style={{
              backgroundColor: isError ? "red" : "green",
            }}
          />
        </div>
      )}

      {verificationCode && (
        <div className="modal-overlay" onClick={handleCloseCalendar}>
          {message && (
            <div
              className="message"
              style={{
                backgroundColor: isError ? "#f8d7da" : "#d4edda",
                border: isError ? "1px solid #f5c6cb" : "1px solid #c3e6cb",
                color: isError ? "red" : "green",
              }}
            >
              <img
                src={isError ? "./warning.png" : "./done.png"}
                alt="status"
                className="message-img"
              />
              <span style={{ color: isError ? "red" : "green" }}>
                {message}
              </span>
              <div
                className="message-timer"
                style={{
                  backgroundColor: isError ? "red" : "green",
                }}
              />
            </div>
          )}
          <div className="verification-modal">
            <img src="./logo.png" />
            <h2>Enter verification code</h2>
            <p>We've sent a verification code to your email</p>
            <div className="verification-code-container">
              {verifCode.map((digit, index) => (
                <input
                  key={index}
                  id={`code-${index}`}
                  type="text"
                  maxLength="1"
                  value={digit}
                  onChange={(e) => handleCodeChange(index, e.target.value)}
                  onKeyDown={(e) => handleKeyDown(index, e)}
                  className="verification-digit-input"
                />
              ))}
            </div>

            <div className="inputs-group">
              <button className="btn" onClick={verifyRegister}>
                Verify
              </button>
              <button
                className="btn"
                onClick={() => {
                  setVerificationCode(false);
                  setVerifCode(["", "", "", "", "", ""]);
                  setSignUpData({
                    username: "",
                    email: "",
                    password: "",
                    role: "student",
                  });
                  setMessage("");
                  setIsError(false);
                }}
              >
                Cancel
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default SignIn;
